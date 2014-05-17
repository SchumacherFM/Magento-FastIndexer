<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
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
        if (false === Mage::helper('schumacherfm_fastindexer')->disableJoinsInCatalogUrlHelper($storeId)) {
            parent::joinTableToEavCollection($collection, $storeId);
        }
        echo __LINE__ . ': ' . $collection->getSelect()->__toString();
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
        if (false === Mage::helper('schumacherfm_fastindexer')->disableJoinsInCatalogUrlHelper($storeId)) {
            parent::joinTableToCollection($collection, $storeId);
        }
        echo __LINE__ . ': ' . $collection->getSelect()->__toString();
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
        if (false === Mage::helper('schumacherfm_fastindexer')->disableJoinsInCatalogUrlHelper($storeId)) {
            parent::joinTableToSelect($select, $storeId);
        }
        echo __LINE__ . ': ' . $select->__toString();
        return $this;
    }
}
