<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */

/**
 * Only applicable for Magento >= 1.8
 *
 * Class SchumacherFM_FastIndexer_Helper_Category_Url_Rewrite
 */
class SchumacherFM_FastIndexer_Helper_Category_Url_Rewrite extends Mage_Catalog_Helper_Category_Url_Rewrite
{

    /**
     * Join url rewrite table to eav collection
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
     * @param int                                       $storeId
     *
     * @return Mage_Catalog_Helper_Category_Url_Rewrite
     */
    public function joinTableToEavCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection, $storeId)
    {
        if (false === Mage::helper('schumacherfm_fastindexer')->optimizeUrlRewriteFlatCategory()) {
            parent::joinTableToEavCollection($collection, $storeId);
        } else {
            $collection->getSelect()->columns('main_table.url_path as request_path');
        }
        SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $collection->getSelect());
        return $this;
    }

    /**
     * Join url rewrite table to collection
     *
     * @param Mage_Catalog_Model_Resource_Category_Flat_Collection $collection
     * @param int                                                  $storeId
     *
     * @return Mage_Catalog_Helper_Category_Url_Rewrite|Mage_Catalog_Helper_Category_Url_Rewrite_Interface
     */
    public function joinTableToCollection(Mage_Catalog_Model_Resource_Category_Flat_Collection $collection, $storeId)
    {
        if (false === Mage::helper('schumacherfm_fastindexer')->optimizeUrlRewriteFlatCategory()) {
            parent::joinTableToCollection($collection, $storeId);
        } else {
            $collection->getSelect()->columns('main_table.url_path as request_path');
        }
        SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $collection->getSelect());
        return $this;
    }

    /**
     * Join url rewrite to select
     *
     * @param Varien_Db_Select $select
     * @param int              $storeId
     *
     * @return Mage_Catalog_Helper_Category_Url_Rewrite
     */
    public function joinTableToSelect(Varien_Db_Select $select, $storeId)
    {
        if (false === Mage::helper('schumacherfm_fastindexer')->optimizeUrlRewriteFlatCategory()) {
            parent::joinTableToSelect($select, $storeId);
        } else {
            $select->columns('main_table.url_path as request_path');
        }
        SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $select);
        return $this;
    }
}
