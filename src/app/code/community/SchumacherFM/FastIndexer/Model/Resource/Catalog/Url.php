<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Catalog_Url extends Mage_Catalog_Model_Resource_Url
{

    /**
     * Used in custom Model_Url
     *
     * @param $productIds
     * @param $storeId
     * @param $lastEntityId
     *
     * @return array
     */
//    public function getProductsByIds($productIds, $storeId, &$lastEntityId)
//    {
//        return $this->_getProducts($productIds, $storeId, $lastEntityId, $lastEntityId);
//    }

    /**
     * Retrieve Product data objects
     * LOE: remove if status(=2) is disabled or visibility(=1) false
     *
     * @param int|array $productIds
     * @param int       $storeId
     * @param int       $entityId
     * @param int       $lastEntityId ref
     *
     * @return array
     */
    protected function _getProducts($productIds, $storeId, $entityId, &$lastEntityId)
    {
        if (true === empty($productIds)) {
            return [];
        }

        if (false === is_array($productIds)) {
            $productIds = [$productIds];
        }

        if ($this->_getHelper()->excludeDisabledProducts($storeId) && $this->_getHelper()->excludeNotVisibleProducts($storeId)) {
            return parent::_getProducts($productIds, $storeId, $entityId, $lastEntityId);
        }

        $products    = parent::_getProducts($productIds, $storeId, $entityId, $lastEntityId);
        $_attributes = [];
        if ($this->_getHelper()->excludeDisabledProducts($storeId)) {
            $_attributes[] = 'status';
        }
        if ($this->_getHelper()->excludeNotVisibleProducts($storeId)) {
            $_attributes[] = 'visibility';
        }
        foreach ($_attributes as $attributeCode) {
            $attributes = $this->_getProductAttribute($attributeCode, array_keys($products), $storeId);
            foreach ($attributes as $productId => $attributeValue) {
                if (($attributeCode == 'status' && $attributeValue == Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                    ||
                    ($attributeCode == 'visibility' && $attributeValue == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
                ) {
                    if (isset($productIds[$productId])) {
                        unset($productIds[$productId]);
                    }
                }
            }
        }
        return $products;
    }

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }
}
