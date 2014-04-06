<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
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
     * @var array
     */
    protected $_isIndexTableListCache = array();

    /**
     * Name of the current indexer
     *
     * @var string
     */
    protected $_currentIndexerCode = null;

    /**
     * If not considered then recursing of get_resource_table_name events
     *
     * @var bool
     */
    protected $_initDone = false;

    /**
     * @var SchumacherFM_FastIndexer_Model_TableIndexerMapper
     */
    protected $_tableIndexerMapper = null;

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    public function initIndexTables(Varien_Event_Observer $observer)
    {
        if (false === $this->getHelper()->isEnabled()) {
            return null;
        }
        $this->_tableIndexerMapper = Mage::getSingleton('schumacherfm_fastindexer/tableIndexerMapper');
        $this->_currentIndexerCode = str_replace(SchumacherFM_FastIndexer_Model_Index_Process::BEFORE_REINDEX_PROCESS_EVENT,
            '', $observer->getEvent()->getName());
        $this->_setResource(Mage::getSingleton('core/resource'));
        $this->_initIndexerTables();
        $this->_initDone = true;
        return null;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function _initIndexerTables()
    {
        $tables = $this->_tableIndexerMapper->getTablesByIndexerCode($this->_currentIndexerCode);
        if (false === $tables) {
            throw new InvalidArgumentException('Cannot find any FastIndexer table mapping for indexer: ' . $this->_currentIndexerCode);
        }
        foreach ($tables as $indexTable => $isSet) {
            $this->_currentTableName   = $indexTable;
            $this->_currentTableSuffix = null; // afaik only set for category tables
            $this->_createShadowTable();
        }
        return true;
    }

    /**
     * @fire resource_get_tablename -> every time you call getTableName ... and that s pretty often ...
     *       it only adds the shadow db name aka schema to the table
     *
     * @param Varien_Event_Observer $observer
     *
     * @return bool
     */
    public function reMapTable(Varien_Event_Observer $observer)
    {
        // run only in shell and maybe later also via backend
        if (false === $this->_initDone || false === $this->getHelper()->isEnabled()) {
            return null;
        }

        $this->_currentTableName   = $observer->getEvent()->getTableName();
        $this->_currentTableSuffix = $observer->getEvent()->getTableSuffix();

        $this->_setResource($observer->getEvent()->getResource());
        $this->_updateResourceTableMapping();
        return null;
    }

    /**
     * for specific tables we need to add the default database name because creating a flat table will have foreign keys
     * to other core tables in the default database.
     * for the rest we just add the shadow db name
     */
    protected function _updateResourceTableMapping()
    {
        $tables = array(
            'core_store'              => 1, // due to recursion must be hardcoded ... i think
            'catalog_category_entity' => 1,
            'catalog_product_entity'  => 1,
        );
        // @todo add eventDispatch
        $currentTable = $this->_getCurrentTableName(false);

        if (isset($tables[$currentTable])) { // @todo check how often that is called
            $this->_setMapper($currentTable, $this->_getCurrentDbName() . '.' . $currentTable);
        } elseif (true === $this->_isIndexTable() || true === $this->_isFlatTable()) {
            $this->_setMapper($currentTable, $this->_getShadowDbName() . '.' . $currentTable);
        }
    }

    /**
     * @param string $_originalTableName
     * @param string $_shadowTableName
     *
     * @return Mage_Core_Model_Resource
     */
    protected function _setMapper($_originalTableName, $_shadowTableName)
    {
        return $this->_getResource()->setMappedTableName($_originalTableName, $_shadowTableName);
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
     * this needs to be execute in another DB because index names are unique within a DB and if we create the shadow tables
     * within the same db as magento then the index names are different
     * @return $this
     */
    protected function _createShadowTable()
    {
        if (false === isset($this->_createdTables[$this->_getCurrentTableName()])) {
            $this->_createShadowTableReal();
        }

        $this->_createdTables[$this->_getCurrentTableName()] = array(
            't' => $this->_getCurrentTableName(false),
            's' => $this->_currentTableSuffix
        ); // singleton
        return $this;
    }

    /**
     * drops the table in the shadow db
     * creates the table in the shadow db
     */
    protected function _createShadowTableReal()
    {
        // if index table or category_flat table then drop in shadow db
        $this->_dropTable($this->_getCurrentTableName(), $this->_getShadowDbName(false, 1));

        // create all non flat index tables
        if (true === $this->_isIndexTable()) {
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->_getShadowDbName(true) . '.' . $this->_getCurrentTableName(true, true) .
                ' LIKE ' . $this->_getCurrentTableName(false, true);
            $this->_rawQuery($sql);
        }
        return true;
    }

    /**
     * <old>
     * Esp. when catalog_product_price runs which calls the stock indexer first and when then the price indexer
     * run, the stocks will get truncated :-(
     *
     * when catalogsearch_fulltext runs then it requires the table cataloginventory_stock_status and this class
     * will drop that table asap when not checking if the tables cataloginventory_stock_status indexer is running.
     * so we need a mapping from table name to indexer name
     * </old>
     * @return bool
     */
    protected function _isIndexTable()
    {
        return $this->_tableIndexerMapper->isIndexTable($this->_currentIndexerCode, $this->_getCurrentTableName(true, false));
    }

    /**
     * When the default indexer of the flat table runs, it drops first the flat table and then creates it new.
     * used connection_name: catalog_write
     *
     * @return bool
     */
    protected function _isFlatTable()
    {
        return $this->_tableIndexerMapper->isFlatTable($this->_currentIndexerCode, $this->_getCurrentTableName(true, false));
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
}