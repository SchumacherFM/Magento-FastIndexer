<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Catalog_Url extends Mage_Catalog_Model_Url
{
    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }

    /**
     * Refresh category and childs rewrites
     * Called when reindexing all rewrites and as a reaction on category change that affects rewrites
     *
     * @param int      $categoryId
     * @param int|null $storeId
     * @param bool     $refreshProducts
     *
     * @return Mage_Catalog_Model_Url
     */
    public function refreshCategoryRewrite($categoryId, $storeId = null, $refreshProducts = true)
    {
        if (true === $this->_getHelper()->disableAllCategoriesInUrlRewrite()) {
            return $this;
        }
        return parent::refreshCategoryRewrite($categoryId, $storeId, $refreshProducts);
    }
}
