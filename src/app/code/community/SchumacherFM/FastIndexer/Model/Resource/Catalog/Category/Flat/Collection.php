<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_Flat_Collection extends Mage_Catalog_Model_Resource_Category_Flat_Collection
{
    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getFiHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }

    /**
     * Enter description here ...
     *
     * @return Mage_Catalog_Model_Resource_Category_Flat_Collection
     */
    public function addUrlRewriteToResult()
    {
        if (false === $this->_getFiHelper()->optimizeUrlRewriteFlatCategory()) { // code is the same in >1.7
            return parent::addUrlRewriteToResult();
        }
        // fastindexer removed the joinLeft() to table core_url_rewrite because data in flat table available!
        $this->getSelect()->columns('main_table.url_path as request_path');

        //SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $this->getSelect());

        return $this;
    }
}
