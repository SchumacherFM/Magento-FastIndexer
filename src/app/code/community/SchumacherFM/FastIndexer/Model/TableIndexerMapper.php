<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableIndexerMapper
{
//_index, _idx, _tmp, catalogsearch_fulltext, cataloginventory_stock_status,
//                tag_summary, core_url_rewrite
    public function isIndexTable($indexerCode, $tableName)
    {
        return strpos($tableName, '_index') !== false ||
        strpos($tableName, '_idx') !== false;
    }

    protected function _getMapping()
    {
        // @todo get all tables for insert/update for each indexer
        $map = array(
            'cataloginventory_stock' => array(
                'cataloginventory_stock_status_idx'  => 1,
                'catalog_product_bundle_stock_index' => 1,
                'cataloginventory_stock_status'      => 1,
            ),
            'catalog_product_price'  => array(
                'catalog_product_index_price_idx'             => 1,
                'catalog_product_index_website'               => 1,
                'catalog_product_index_tier_price'            => 1,
                'catalog_product_index_group_price'           => 1,
                'catalog_product_index_price_final_idx'       => 1,
                'catalog_product_index_price_opt_agr_idx'     => 1,
                'catalog_product_index_price_opt_idx'         => 1,
                'catalog_product_index_price_downlod_idx'     => 1,
                'catalog_product_index_price_cfg_opt_agr_idx' => 1,
                'catalog_product_index_price_cfg_opt_idx'     => 1,
                'catalog_product_index_price_bundle_idx'      => 1,
                'catalog_product_index_price_bundle_sel_idx'  => 1,
                'catalog_product_index_price_bundle_opt_idx'  => 1,
                'catalog_product_index_price'                 => 1,
            ),
        );
    }
}
