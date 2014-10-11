<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_Flat extends SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_FlatAbstract
{
    /**
     * Load nodes by parent id
     *
     * @param Mage_Catalog_Model_Category|int $parentNode
     * @param integer                         $recursionLevel
     * @param integer                         $storeId
     *
     * @return Mage_Catalog_Model_Resource_Category_Flat
     */
    protected function _loadNodes($parentNode = null, $recursionLevel = 0, $storeId = 0)
    {
        if (false === $this->_getFiHelper()->optimizeUrlRewriteFlatCategory17()) {
            return parent::_loadNodes($parentNode, $recursionLevel, $storeId);
        }
        return $this->_fastIndexerLoadNodes($parentNode, $recursionLevel, $storeId);
    }
}
