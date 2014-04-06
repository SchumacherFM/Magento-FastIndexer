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
    protected $_map = array(

        'catalog_product_attribute' => array(
            'catalog_product_index_eav_tmp'         => 1,
            'catalog_product_index_eav_idx'         => 1,
            'catalog_product_index_eav'             => 1,
            'catalog_product_index_eav_decimal_tmp' => 1,
            'catalog_product_index_eav_decimal_idx' => 1,
            'catalog_product_index_eav_decimal'     => 1,
        ),
        'cataloginventory_stock'    => array(
            'cataloginventory_stock_status_idx'  => 1,
            'catalog_product_bundle_stock_index' => 1,
            'cataloginventory_stock_status'      => 1,
        ),
        'catalog_category_product'  => array(
            'catalog_category_product_index'          => 1,
            'catalog_category_product_index_enbl_idx' => 1,
            'catalog_category_product_index_enbl_tmp' => 1,
            'catalog_category_product_index_idx'      => 1,
            'catalog_category_product_index_tmp'      => 1,
            'catalog_category_anc_categs_index_idx'   => 1,
            'catalog_category_anc_categs_index_tmp'   => 1,
            'catalog_category_anc_products_index_idx' => 1,
            'catalog_category_anc_products_index_tmp' => 1,
        ),
        'catalog_product_price'     => array(
            'catalog_product_index_price_idx'             => 1,
            'catalog_product_index_website'               => 1,
            'catalog_product_index_tier_price'            => 1,
            'catalog_product_index_group_price'           => 1,
            'catalog_product_index_price_final_idx'       => 1,
            'catalog_product_index_price_opt_agr_idx'     => 1,
            'catalog_product_index_price_opt_idx'         => 1,
            'catalog_product_index_price_downlod_idx'     => 1, // official table name
            'catalog_product_index_price_download_idx'    => 1, // maybe some one fixed it ...
            'catalog_product_index_price_cfg_opt_agr_idx' => 1,
            'catalog_product_index_price_cfg_opt_idx'     => 1,
            'catalog_product_index_price_bundle_idx'      => 1,
            'catalog_product_index_price_bundle_sel_idx'  => 1,
            'catalog_product_index_price_bundle_opt_idx'  => 1,
            'catalog_product_index_price'                 => 1,
        ),
        'catalogsearch_fulltext'    => array(
            'catalogsearch_fulltext' => 1,
        ),
        'tag_summary'               => array(
            'tag_summary' => 1,
        ),
        'catalog_url'               => array(
            'core_url_rewrite' => 1,
        ),
        'catalogpermissions'        => array(
            'enterprise_catalogpermissions_index'         => 1,
            'enterprise_catalogpermissions_index_product' => 1,
        ),
        'targetrule'                => array(
            'enterprise_targetrule_index'           => 1,
            'enterprise_targetrule_index_related'   => 1,
            'enterprise_targetrule_index_crosssell' => 1,
            'enterprise_targetrule_index_upsell'    => 1,
        ),

    );

    /**
     * due to singleton called only once
     */
    public function __construct()
    {
        Mage::dispatchEvent('fastindexer_mapping_table', array('mapper' => $this));
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return bool
     */
    public function isIndexTable($indexerCode, $tableName)
    {
        return isset($this->_map[$indexerCode]) && isset($this->_map[$indexerCode][$tableName]);
    }

    /**
     * @param string $indexerCode
     * @param string $tableName
     *
     * @return $this
     */
    public function updateMap($indexerCode, $tableName)
    {
        $this->_map[$indexerCode][$tableName] = 1;
        return $this;
    }

    /**
     * @param array $map
     *
     * @return $this
     */
    public function setMap(array $map)
    {
        $this->_map = $map;
        return $this;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        return $this->_map;
    }

    /**
     * @param $indexerCode
     *
     * @return array
     */
    public function getTablesByIndexerCode($indexerCode)
    {
        return isset($this->_map[$indexerCode]) ? $this->_map[$indexerCode] : false;
    }
}
