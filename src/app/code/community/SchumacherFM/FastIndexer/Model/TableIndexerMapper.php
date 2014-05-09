<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableIndexerMapper
{
    /**
     * No need to provide the tablePrefix as this will be added afterwards in getTableName.
     * No need to add catalog_product_flat or catalog_category_flat, these are special cases.
     * First key indexerCode, second key table name
     *
     * @var array
     */
    protected $_mapIndexTables = [

        'catalog_product_attribute' => [
            'catalog_product_index_eav_tmp'         => 1,
            'catalog_product_index_eav_idx'         => 1,
            'catalog_product_index_eav'             => 1,
            'catalog_product_index_eav_decimal_tmp' => 1,
            'catalog_product_index_eav_decimal_idx' => 1,
            'catalog_product_index_eav_decimal'     => 1,
        ],
        'cataloginventory_stock'    => [
            'cataloginventory_stock_status'      => 1,
            'cataloginventory_stock_status_idx'  => 1,
            'cataloginventory_stock_status_tmp'  => 1,
            'catalog_product_bundle_stock_index' => 1,
        ],
        'catalog_category_product'  => [
            'catalog_category_product_index'          => 1,
            'catalog_category_product_index_enbl_idx' => 1,
            'catalog_category_product_index_enbl_tmp' => 1,
            'catalog_category_product_index_idx'      => 1,
            'catalog_category_product_index_tmp'      => 1,
            'catalog_category_anc_categs_index_idx'   => 1,
            'catalog_category_anc_categs_index_tmp'   => 1,
            'catalog_category_anc_products_index_idx' => 1,
            'catalog_category_anc_products_index_tmp' => 1,
        ],
        'catalog_product_price'     => [
            'catalog_product_index_price_idx'             => 1,
            'catalog_product_index_website'               => 1,
            'catalog_product_index_tier_price'            => 1,
            'catalog_product_index_group_price'           => 1,
            'catalog_product_index_price_final_idx'       => 1,
            'catalog_product_index_price_opt_agr_idx'     => 1,
            'catalog_product_index_price_opt_idx'         => 1,
            'catalog_product_index_price_downlod_idx'     => 1, // official table name
            //'catalog_product_index_price_download_idx'    => 1, // maybe some one fixed it ...
            'catalog_product_index_price_cfg_opt_agr_idx' => 1,
            'catalog_product_index_price_cfg_opt_idx'     => 1,
            'catalog_product_index_price_bundle_idx'      => 1,
            'catalog_product_index_price_bundle_sel_idx'  => 1,
            'catalog_product_index_price_bundle_opt_idx'  => 1,
            'catalog_product_index_price'                 => 1,
        ],
        'catalogsearch_fulltext'    => [
            'catalogsearch_fulltext' => 1,
        ],
        'tag_summary'               => [
            'tag_summary' => 1,
        ],
        'catalog_url'               => [
            'core_url_rewrite' => 1,
        ],
        'catalogpermissions'        => [
            'enterprise_catalogpermissions_index'         => 1,
            'enterprise_catalogpermissions_index_product' => 1,
        ],
        'targetrule'                => [
            'enterprise_targetrule_index'           => 1,
            'enterprise_targetrule_index_related'   => 1,
            'enterprise_targetrule_index_crosssell' => 1,
            'enterprise_targetrule_index_upsell'    => 1,
        ],

    ];

    /**
     * IndexerCode => empty
     *
     * @var array
     */
    protected $_mapFlatTables = [
        'catalog_product_flat'  => [
            Mage_Catalog_Model_Product_Flat_Indexer::ENTITY => 1, // hardcoded due to recursion and needed for isFlatTable
        ],
        'catalog_category_flat' => [],
    ];
    /**
     * @var array
     */
    protected $_stores = null;

    /**
     * due to singleton called only once
     */
    public function __construct(array $stores = null)
    {
        if (false === empty($stores)) {
            $this->_stores = $stores;
        }
        $this->_initFlatTables();
        Mage::dispatchEvent('fastindexer_mapping_table', ['mapper' => $this]);
    }

    /**
     * @return array
     */
    public function getStores()
    {
        if (null === $this->_stores) {
            $this->_stores = Mage::app()->getStores();
        }
        return $this->_stores;
    }

    /**
     *
     */
    protected function _initFlatTables()
    {
        /** @var Mage_Catalog_Model_Resource_Category_Flat $flatCategory */
        $flatCategory = Mage::getResourceModel('catalog/category_flat'); // no singleton because of the _tables cache!!!
        /** @var Mage_Catalog_Model_Resource_Product_Flat_Indexer $flatProduct */
        $flatProduct = Mage::getResourceModel('catalog/product_flat_indexer'); // no singleton because of the _tables cache!!!

        foreach ($this->_mapFlatTables as $indexerCode => &$empty) {
            foreach ($this->getStores() as $store) {
                /** @var Mage_Core_Model_Store $store */
                $flatTableName         = strpos($indexerCode, 'category_flat') !== false
                    ? $flatCategory->getMainStoreTable($store->getId())
                    : $flatProduct->getFlatTableName($store->getId());
                $empty[$flatTableName] = 1;
            }
        }
        // reset table cache in resource model Mage_Core_Model_Resource_Db_Abstract
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return bool
     */
    public function isIndexTable($indexerCode, $tableName)
    {
        return isset($this->_mapIndexTables[$indexerCode]) && isset($this->_mapIndexTables[$indexerCode][$tableName]);
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return bool
     */
    public function isFlatTable($indexerCode, $tableName)
    {
        return isset($this->_mapFlatTables[$indexerCode]) && isset($this->_mapFlatTables[$indexerCode][$tableName]);
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return $this
     */
    public function updateIndexMap($indexerCode, $tableName)
    {
        $this->_mapIndexTables[$indexerCode][$tableName] = 1;
        return $this;
    }

    /**
     * @param array $map
     *
     * @return $this
     */
    public function setIndexMap(array $map)
    {
        $this->_mapIndexTables = $map;
        return $this;
    }

    /**
     * @return array
     */
    public function getIndexMap()
    {
        return $this->_mapIndexTables;
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return $this
     */
    public function updateFlatMap($indexerCode, $tableName)
    {
        $this->_mapFlatTables[$indexerCode][$tableName] = 1;
        return $this;
    }

    /**
     * @param array $map
     *
     * @return $this
     */
    public function setFlatMap(array $map)
    {
        $this->_mapFlatTables = $map;
        return $this;
    }

    /**
     * @return array
     */
    public function getFlatMap()
    {
        return $this->_mapFlatTables;
    }

    /**
     * @param $indexerCode
     *
     * @return array
     */
    public function getTablesByIndexerCode($indexerCode)
    {
        $tables = isset($this->_mapIndexTables[$indexerCode]) ? $this->_mapIndexTables[$indexerCode] : false;
        if (false === $tables) {
            $tables = isset($this->_mapFlatTables[$indexerCode]) ? $this->_mapFlatTables[$indexerCode] : false;
            if (isset($tables[Mage_Catalog_Model_Product_Flat_Indexer::ENTITY])) {
                unset($tables[Mage_Catalog_Model_Product_Flat_Indexer::ENTITY]);
            }
        }
        return $tables;
    }
}
