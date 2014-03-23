<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
abstract class SchumacherFM_FastIndexer_Model_AbstractTable
{
    /**
     * @see Varien_Db_Adapter_Pdo_Mysql::_checkDdlTransaction
     */
    const DISABLE_CHECKDDLTRANSACTION = '/*disable _checkDdlTransaction*/ ';

    /**
     * @var Mage_Core_Model_Resource
     */
    protected $_resource = null;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_shadowConnection = null;

    protected $_isEchoOn = false;
    protected $_shadowDbName = null;

    /**
     * @param bool $quote
     *
     * @return string
     */
    protected function _getShadowDbName($quote = false)
    {
        if (null === $this->_shadowDbName) {
            $this->_shadowDbName = trim(Mage::getStoreConfig('system/fastindexer/dbName'));
        }
        if (true === $quote) {
            return $this->_quote($this->_shadowDbName);
        }
        return $this->_shadowDbName;
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = $this->_getResource()->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        }
        return $this->_connection;
    }

    /**
     * @param Mage_Core_Model_Resource $resource
     *
     * @return $this
     */
    protected function _setResource(Mage_Core_Model_Resource $resource)
    {
        $this->_resource = $resource;
        return $this;
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return $this->_resource;
    }

    /**
     * returns all non PRI columns
     *
     * @param $tableName
     *
     * @return array
     */
    protected function _getColumnsFromTable($tableName)
    {
        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt          = $this->_getConnection()->query('SHOW COLUMNS FROM ' . $this->_getTableName($tableName));
        $columnsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns       = array();
        foreach ($columnsResult as $col) {
            if ($col['Key'] !== 'PRI') {
                $columns[] = '`' . $col['Field'] . '`';
            }
        }
        return $columns;
    }

    /**
     * @param $tableName
     *
     * @return int
     */
    protected function _getTableCount($tableName)
    {
        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt    = $this->_getConnection()->query('SELECT COUNT(*) AS counted FROM ' . $this->_getTableName($tableName));
        $counter = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return isset($counter[0]) ? (int)$counter[0]['counted'] : 0;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    protected function _dropTable($tableName)
    {
        $this->_rawQuery('DROP TABLE IF EXISTS ' . $this->_getTableName($tableName));
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function _getTableName($tableName)
    {
        return $this->_quote($tableName);
    }

    /**
     * @param string $sql
     *
     * @return Zend_Db_Statement_Interface
     */
    protected function _rawQuery($sql)
    {
        if (true === $this->_isEchoOn) {
            Mage::log($sql, null, 'findexer.log');
        }
        return $this->_getConnection()->raw_query($sql);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function _quote($string)
    {
        return $this->_getConnection()->quoteIdentifier($string);
    }
}