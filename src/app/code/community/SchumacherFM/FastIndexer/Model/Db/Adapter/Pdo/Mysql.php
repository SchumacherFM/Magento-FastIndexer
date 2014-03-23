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
     * Bugfix
     * Retrieve Create Table SQL
     *
     * @param string $tableName
     * @param string $schemaName
     *
     * @return string
     */
    public function getCreateTable($tableName, $schemaName = null)
    {
        Zend_Debug::dump(['It works!', $tableName, $schemaName]);
        exit;

        $cacheKey = $this->_getTableName($tableName, $schemaName);
        $ddl      = $this->loadDdlCache($cacheKey, self::DDL_CREATE);
        if ($ddl === false) {
            $sql = 'SHOW CREATE TABLE ' . $this->quoteIdentifier($tableName);
            $ddl = $this->raw_fetchRow($sql, 'Create Table');
            $this->saveDdlCache($cacheKey, self::DDL_CREATE, $ddl);
        }

        return $ddl;
    }
}
