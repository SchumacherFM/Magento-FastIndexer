<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.schumacher.fm)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_FastIndexer extends Varien_Object
{
    const FINDEX_TBL_PREFIX = 'afstidex_';

    /**
     * @var array
     */
    protected $_createdTables = NULL;

    /**
     * @var Mage_Core_Model_Resource
     */
    protected $_resource = null;
    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @var string
     */
    protected $_currentTableName = '';

    /**
     * @param Varien_Event_Observer $event
     *
     * @return bool
     */
    public function changeTableName(Varien_Event_Observer $event)
    {
        if (!$this->_runsOnCommandLine() || !Mage::helper('findex')->isEnabled()) { // run only in shell
            return TRUE;
        }

        /** @var Mage_Core_Model_Resource _resource */
        $this->_resource = $event->getEvent()->getResource();
        /** @var Varien_Db_Adapter_Pdo_Mysql _connection */
        $this->_connection       = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $this->_currentTableName = $event->getEvent()->getTableName();
        $tableSuffix             = $event->getEvent()->getTableSuffix();
        if (!empty($tableSuffix)) {
            $this->_currentTableName .= '_' . $tableSuffix;
        }

        if ($this->_isIndexTable() || $this->_isFlatTable()) {
            $newTableName = $this->_getNewTableName();
            $this->_createTable($newTableName);
        }
    }

    protected function _runsOnCommandLine()
    {
        return isset($_SERVER['argv']) && (int)$_SERVER['argc'] > 0;
    }

    /**
     * @param string $newTableName
     *
     * @return bool
     */
    protected function _createTable($newTableName)
    {
        if ($this->_isIndexTable()) {
            if ($this->_existsNewTableInDb($newTableName)) {
                //          $this->_connection->query('TRUNCATE `' . $newTableName);
            } else {
                $this->_connection->query('CREATE TABLE `' . $newTableName . '` like `' . $this->_currentTableName . '`');
            }
        }
        $this->_resource->setMappedTableName($this->_currentTableName, $newTableName);
        $this->_createdTables[$newTableName] = str_replace(self::FINDEX_TBL_PREFIX, '', $this->_currentTableName);
        return TRUE;
    }

    /**
     * @param string $newTableName
     *
     * @return bool
     */
    protected function _existsNewTableInDb($newTableName)
    {
        if ($this->_createdTables === null) {
            $this->_createdTables = array();
            /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
            $stmt   = $this->_connection->query('SHOW TABLES LIKE \'' . self::FINDEX_TBL_PREFIX . '%\'');
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tables as $table) {
                $tn                        = reset($table);
                $this->_createdTables[$tn] = str_replace(self::FINDEX_TBL_PREFIX, '', $tn);
            }
        }
        return isset($this->_createdTables[$newTableName]);
    }

    /**
     * @return string
     */
    protected function _getNewTableName()
    {
        if (strstr($this->_currentTableName, self::FINDEX_TBL_PREFIX) !== FALSE) {
            return $this->_currentTableName;
        }
        return self::FINDEX_TBL_PREFIX . $this->_currentTableName;
    }

    /**
     * @return bool
     */
    protected function _isIndexTable()
    {
        return
            strpos($this->_currentTableName, '_index') > 2 ||
            strpos($this->_currentTableName, '_idx') > 2 ||
            strstr($this->_currentTableName, 'core_url_rewrite') !== FALSE;
    }

    /**
     * @return bool
     */
    protected function _isFlatTable()
    {
        return
            strstr($this->_currentTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_CATEGORY_FLAT) !== FALSE ||
            strstr($this->_currentTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_PRODUCT_FLAT) !== FALSE;
    }

    public function getTables()
    {
        return $this->_createdTables;
    }

    public function unsetTables()
    {
        $this->_createdTables = null;
        return $this;
    }

}