<?php

/**
 * Mysql PDO DB adapter
 */
class SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql extends Varien_Db_Adapter_Pdo_Mysql
{
    protected $_shadowDbName = null;
    protected $_currentDbName = null;

    /**
     * @return string
     */
    protected function _getCurrentDbName()
    {
        if (null === $this->_currentDbName) {
            $this->_currentDbName = (string)Mage::getConfig()->getNode(SchumacherFM_FastIndexer_Helper_Data::CONFIG_DB_NAME);
            if (empty($this->_currentDbName)) {
                Mage::throwException('Current DB Name cannot be empty!');
            }
        }
        return $this->_currentDbName;
    }

    /**
     * @return null|string
     */
    protected function _getShadowDbName()
    {
        if (null === $this->_shadowDbName) {
            $this->_shadowDbName = trim(Mage::getStoreConfig('system/fastindexer/dbName'));
            if (empty($this->_shadowDbName)) {
                Mage::throwException('Shadow DB Name cannot be empty!');
            }
        }
        return $this->_shadowDbName;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function _removeDbNames($tableName)
    {
        $tableName = str_replace($this->_getShadowDbName() . '.', '', $tableName);
        return str_replace($this->_getCurrentDbName() . '.', '', $tableName);
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
        return parent::getIndexName($this->_removeDbNames($tableName), $fields, $indexType);
    }

    /**
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
            $this->_removeDbNames($priTableName),
            $priColumnName,
            $this->_removeDbNames($refTableName),
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
}
