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
     * @fire resource_get_tablename -> every time you call getTableName ... and that s pretty often ...
     *
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
        $this->_setResource($event->getEvent()->getResource());

        if (true === $this->_isIndexTable() || true === $this->_isFlatTable()) {
            $this->_stores = Mage::app()->getStores();
            // table suffix is needed for the flat tables to append _[0-9]
            $this->_createShadowTable();
        }
        $this->_updateTableMapperForForeignKeys();
    }

    /**
     * for specific tables we need to add the default database name because creating a flat table will have foreign keys
     * to other core tables
     */
    protected function _updateTableMapperForForeignKeys()
    {
        $tables = array(
            'core_store'              => 1,
            'catalog_category_entity' => 1,
            'catalog_product_entity'  => 1,
        );
        $curTab = $this->_getCurrentTableName(false);
        if (isset($tables[$curTab])) {
            $this->_setMapper($curTab, $this->_getCurrentDbName() . '.' . $curTab);
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
        if (false === isset($this->_createdTables[$this->_getCurrentTableName(false)])) {
            $this->_createShadowTableReal();
        }
        $this->_setMapper($this->_getCurrentTableName(false), $this->_getShadowDbName() . '.' . $this->_getCurrentTableName(false));

        $this->_createdTables[$this->_getCurrentTableName(false)] = array(
            't' => $this->_getCurrentTableName(false),
            's' => $this->_currentTableSuffix
        ); // singleton
        return $this;
    }

    /**
     *
     */
    protected function _createShadowTableReal()
    {
        if (true === $this->_isFlatTable()) {
            // check just in case something fails we drop the product_flat table
            if (true === $this->_isProductFlatTable($this->_getCurrentTableName(false))) {
                foreach ($this->_stores as $store) {
                    /** @var Mage_Core_Model_Store $store */
                    $this->_currentTableSuffix = $store->getId();
                    $this->_dropTable($this->_getCurrentTableName(), $this->_getShadowDbName(false, 1));
                    $this->_currentTableSuffix = null;
                }
            } else {
                $this->_dropTable($this->_getCurrentTableName(), $this->_getShadowDbName(false, 1));
            }
        } else {
            if ($this->_isIndexTable() && false === $this->_existsTableInShadowDb()) {
                $sql = self::DISABLE_CHECKDDLTRANSACTION . 'CREATE TABLE ' . $this->_getShadowDbName(true) . '.' . $this->_getCurrentTableName(true, true) .
                    ' LIKE ' . $this->_quote($this->_currentTableName);
                $this->_rawQuery($sql);
            }
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
     * @return bool
     */
    protected function _existsTableInShadowDb()
    {
        // could also be the shadowConnection
        return $this->_getConnection()->isTableExists($this->_getCurrentTableName(), $this->_getShadowDbName());
    }

    /**
     * catalogsearch_fulltext can be removed because the indexer of catalogsearch_fulltext has a cleanIndex
     * method in Mage_CatalogSearch_Model_Resource_Fulltext_Engine which deletes the table.
     * also the inventory
     * truncate maybe more faster and accurate ???
     *
     * But in total the tables won't be empty with FastIndexer and the frontend user will nothing notice. So keep them in here.
     *
     * @return bool
     */
    protected function _isIndexTable()
    {
        return
            strpos($this->_currentTableName, '_index') !== false ||
            strpos($this->_currentTableName, '_idx') !== false ||
            strpos($this->_currentTableName, 'catalogsearch_fulltext') !== false ||
            $this->_isUrlRewriteTable($this->_currentTableName) !== false;
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
     * When the default indexer of the flat table runs. it drops first the flat table and then creates it new.
     * used connection_name: catalog_write
     *
     * @return bool
     */
    protected function _isFlatTable()
    {
        return Mage::helper('schumacherfm_fastindexer')->isFlatTablePrefix($this->_currentTableName);
    }
}