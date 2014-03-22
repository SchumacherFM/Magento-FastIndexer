<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableCreator extends SchumacherFM_FastIndexer_Model_AbstractTable
{
    /**
     * @var array
     */
    protected $_createdTables = array();

    /**
     * @var string
     */
    protected $_currentTableName = '';

    /**
     * @var string
     */
    protected $_currentTableSuffix = '';

    /**
     * @param Varien_Event_Observer $event
     *
     * @return bool
     */
    public function createTable(Varien_Event_Observer $event)
    {
        // run only in shell and maybe later also via backend
        if (false === $this->_runsOnCommandLine() || false === Mage::helper('schumacherfm_fastindexer')->isEnabled()) {
            return null;
        }
        $this->_currentTableName   = $event->getEvent()->getTableName();
        $this->_currentTableSuffix = $event->getEvent()->getTableSuffix();

        if ($this->_isIndexTable() || $this->_isFlatTable()) {

            /** @var Mage_Core_Model_Resource _resource */
            $this->_resource = $event->getEvent()->getResource();
            /** @var Varien_Db_Adapter_Pdo_Mysql _connection */
            $this->_connection = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

            // table suffix is needed for the flat tables to append _[0-9]
            $this->_createShadowTable();
        }
    }

    /**
     * @param bool $withSuffix
     * @param bool $quote
     *
     * @return string
     */
    protected function _getCurrentTableName($withSuffix = true, $quote = false)
    {
        $return = $this->_currentTableName .
            (true === $withSuffix && false === empty($this->_currentTableSuffix)
                ? '_' . $this->_currentTableSuffix
                : '');
        if (true === $quote) {
            return $this->_quote($return);
        }
        return $return;
    }

    /**
     * this needs to be execute in another DB because index names are unique within a DB and if the create the shadow tables
     * within the same db as magento then the index names are different
     * @return $this
     */
    protected function _createShadowTable()
    {
        if ($this->_isIndexTable() && !$this->_existsTableInShadowDb()) {
            $sql = self::DISABLE_CHECKDDLTRANSACTION . 'CREATE TABLE ' . $this->_getShadowDbName(true) . '.' . $this->_getCurrentTableName(true, true) .
                ' LIKE ' . $this->_quote($this->_currentTableName);
            $this->_rawQuery($sql);
        }

        $this->_setMapper($this->_getCurrentTableName(false), $this->_getShadowDbName() . '.' . $this->_getCurrentTableName(false));
        return $this;
    }

    /**
     * @param string $_originalTableName
     * @param string $_shadowTableName
     */
    protected function _setMapper($_originalTableName, $_shadowTableName)
    {
        $this->_resource->setMappedTableName($_originalTableName, $_shadowTableName);
        $this->_createdTables[] = $_originalTableName;
    }

    /**
     * @return bool
     */
    protected function _existsTableInShadowDb()
    {
        // could also be the shadowConnection
        $exists = $this->_getConnection()->isTableExists($this->_getCurrentTableName(), $this->_getShadowDbName());
        if (true === $exists) {
            // truncate shadow table
            $this->_getConnection()->dropTable($this->_getCurrentTableName(), $this->_getShadowDbName());
            $exists = false;
        }
        return $exists;
    }

    /**
     * @return bool
     */
    protected function _isIndexTable()
    {
        return
            strpos($this->_currentTableName, '_index') !== false ||
            strpos($this->_currentTableName, '_idx') !== false ||
            strpos($this->_currentTableName, 'catalogsearch_fulltext') !== false ||
            strstr($this->_currentTableName, 'core_url_rewrite') !== false;
    }

    /**
     * @return bool
     */
    protected function _isFlatTable()
    {
        return
            strstr($this->_currentTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_CATEGORY_FLAT) !== false ||
            strstr($this->_currentTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_PRODUCT_FLAT) !== false;
    }

    /**
     * will be called from tableRollBack class
     *
     * @return array|null
     */
    public function getTables()
    {
        return $this->_createdTables;
    }

    /**
     * unsets to internal table cache to reread the table names again from the DB
     *
     * @return $this
     */
    public function unsetTables()
    {
        $this->_createdTables = array();
        return $this;
    }

    /**
     * @return bool
     */
    protected function _runsOnCommandLine()
    {
        return isset($_SERVER['argv']) && isset($_SERVER['argc']) && (int)$_SERVER['argc'] > 0;
    }
}