<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */

/**
 * Mysql PDO DB adapter with bug fixes
 */
class SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql extends Varien_Db_Adapter_Pdo_Mysql
{

    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_fastIndexerHelper = null;

    /**
     * This value must be set in the local.xml
     * node: config/global/resources/default_setup/connection/fiQuoteOpt
     *
     * @return bool
     */
    public function enableDbQuoteOptimization()
    {
        return isset($this->_config['fiQuoteOpt']) && $this->_config['fiQuoteOpt'] === '1';
    }

    /**
     * @param SchumacherFM_FastIndexer_Helper_Data $helper
     *
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    public function getFastIndexerHelper(SchumacherFM_FastIndexer_Helper_Data $helper = null)
    {
        if (null !== $helper) {
            $this->_fastIndexerHelper = $helper;
        }
        if (null === $this->_fastIndexerHelper) {
            $this->_fastIndexerHelper = Mage::helper('schumacherfm_fastindexer');
        }
        return $this->_fastIndexerHelper;
    }

    /**
     * Removes the shadow db name and replaces it with the default setup db name
     *
     * @param string $tableName
     *
     * @return string
     */
    protected function _removeShadowDbName($tableName)
    {
        $tableName = str_replace($this->getFastIndexerHelper()->getShadowDbName(1) . '.', '', $tableName);
        return str_replace($this->getFastIndexerHelper()->getDefaultSetupDbName() . '.', '', $tableName);
    }

    /**
     * Remove shadow db name for creating index names
     *
     * Retrieve valid index name
     * Check index name length and allowed symbols
     *
     * @param string       $tableName
     * @param string|array $fields the columns list
     * @param string       $indexType
     *
     * @return string
     */
    public function getIndexName($tableName, $fields, $indexType = '')
    {
        return parent::getIndexName($this->_removeShadowDbName($tableName), $fields, $indexType);
    }

    /**
     * Bugfix to remove the DB name
     * Retrieve valid foreign key name
     * Check foreign key name length and allowed symbols
     *
     * @param string $priTableName
     * @param string $priColumnName
     * @param string $refTableName
     * @param string $refColumnName
     *
     * @return string
     */
    public function getForeignKeyName($priTableName, $priColumnName, $refTableName, $refColumnName)
    {
        return parent::getForeignKeyName(
            $this->_removeShadowDbName($priTableName),
            $priColumnName,
            $this->_removeShadowDbName($refTableName),
            $refColumnName
        );
    }

    /**
     * Bugfix because the schemaName hasn't been considered
     * Retrieve Create Table SQL
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function getCreateTable($tableName, $schemaName = null)
    {
        $cacheKey = $this->_getTableName($tableName, $schemaName);
        $ddl      = $this->loadDdlCache($cacheKey, self::DDL_CREATE);
        if ($ddl === false) {
            $schemaName = empty($schemaName) ? '' : $this->quoteIdentifier($schemaName) . '.';
            $sql        = 'SHOW CREATE TABLE ' . $schemaName . $this->quoteIdentifier($tableName);
            $ddl        = $this->raw_fetchRow($sql, 'Create Table');
            $this->saveDdlCache($cacheKey, self::DDL_CREATE, $ddl);
        }

        return $ddl;
    }

    /**
     * Bugfix in Regex line 2 for REFERENCES like `test`.`table`
     *
     * Retrieve the foreign keys descriptions for a table.
     *
     * The return value is an associative array keyed by the UPPERCASE foreign key,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * FK_NAME          => string; original foreign key name
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * REF_SCHEMA_NAME  => string; name of reference database or schema
     * REF_TABLE_NAME   => string; reference table name
     * REF_COLUMN_NAME  => string; reference column name
     * ON_DELETE        => string; action type on delete row
     * ON_UPDATE        => string; action type on update row
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return array
     */
    public function getForeignKeys($tableName, $schemaName = null)
    {
        $cacheKey = $this->_getTableName($tableName, $schemaName);
        $ddl      = $this->loadDdlCache($cacheKey, self::DDL_FOREIGN_KEY);
        if ($ddl === false) {
            $ddl       = array();
            $createSql = $this->getCreateTable($tableName, $schemaName);

            // collect CONSTRAINT
            $regExp  = '#,\s+CONSTRAINT `([^`]*)` FOREIGN KEY \(`([^`]*)`\) '
                . 'REFERENCES (`[^`]*`\.)?`([^`]*)` \(`([^`]*)`\)'
                . '( ON DELETE (RESTRICT|CASCADE|SET NULL|NO ACTION))?'
                . '( ON UPDATE (RESTRICT|CASCADE|SET NULL|NO ACTION))?#';
            $matches = array();
            preg_match_all($regExp, $createSql, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $ddl[strtoupper($match[1])] = array(
                    'FK_NAME'         => $match[1],
                    'SCHEMA_NAME'     => $schemaName,
                    'TABLE_NAME'      => $tableName,
                    'COLUMN_NAME'     => $match[2],
                    'REF_SHEMA_NAME'  => isset($match[3]) ? $match[3] : $schemaName,
                    'REF_TABLE_NAME'  => $match[4],
                    'REF_COLUMN_NAME' => $match[5],
                    'ON_DELETE'       => isset($match[6]) ? $match[7] : '',
                    'ON_UPDATE'       => isset($match[8]) ? $match[9] : ''
                );
            }
            $this->saveDdlCache($cacheKey, self::DDL_FOREIGN_KEY, $ddl);
        }
        return $ddl;
    }

    /**
     * Safely quotes a value for an SQL statement.
     *
     * If an array is passed as the value, the array values are quoted
     * and then returned as a comma-separated string.
     *
     * @param mixed $value The value to quote.
     * @param mixed $type  OPTIONAL the SQL datatype name, or constant, or null.
     *
     * @return mixed An SQL-safe quoted value (or string of separated values).
     */
    public function quote($value, $type = null)
    {
        if (false === $this->enableDbQuoteOptimization()) {
            return parent::quote($value, $type);
        }
        $this->_connect();

        if ($value instanceof Zend_Db_Select) {
            return '(' . $value->assemble() . ')';
        }

        if ($value instanceof Zend_Db_Expr) {
            return $value->__toString();
        }

        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = $this->quote($val, $type);
            }
            return implode(', ', $value);
        }

        if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {
            $quotedValue = '0';
            switch ($this->_numericDataTypes[$type]) {
                case Zend_Db::INT_TYPE: // 32-bit integer
                    $quotedValue = (string)intval($value);
                    break;
                case Zend_Db::BIGINT_TYPE: // 64-bit integer
                    // ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
                    // are not supported here, because these are string
                    // literals, not numeric literals.
                    if (preg_match('/^(
                          [+-]?                  # optional sign
                          (?:
                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
                          )
                        )/x',
                        (string)$value, $matches)
                    ) {
                        $quotedValue = $matches[1];
                    }
                    break;
                case Zend_Db::FLOAT_TYPE: // float or decimal
                    $quotedValue = sprintf('%F', $value);
            }
            return $quotedValue;
        }

        return $this->_quote($this->castToNumeric($value));
    }

    /**
     * Special handling for PDO query().
     * All bind parameter names must begin with ':'.
     *
     * @param string|Zend_Db_Select $sql  The SQL statement with placeholders.
     * @param mixed                 $bind An array of data or data itself to bind to the placeholders.
     *
     * @return Varien_Db_Statement_Pdo_Mysql
     * @throws Zend_Db_Adapter_Exception To re-throw PDOException.
     */
    public function query($sql, $bind = array())
    {
        if (false === $this->enableDbQuoteOptimization()) {
            return parent::query($sql, $bind);
        }

        if (is_array($bind) && count($bind) > 0) {
            foreach ($bind as $k => $v) {
                $bind[$k] = $this->castToNumeric($v);
            }
        }
        return parent::query($sql, $bind);
    }

    /**
     * optimization, turning string values into real int/float
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function castToNumeric($value)
    {
        if (true === is_numeric($value)) {
            $value = (float)$value;
            if ($value === ((float)($value | 0))) { // int to float
                return ($value | 0); //int
            }
        }
        return $value;
    }
}
