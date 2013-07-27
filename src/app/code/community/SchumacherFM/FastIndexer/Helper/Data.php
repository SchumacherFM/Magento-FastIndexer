<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.schumacher.fm)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Fast Indexer enabled
     */
    const XML_PATH_FASTINDEXER_ENABLE = 'system/fastindexer/enable';
    const CATALOG_CATEGORY_FLAT       = 'catalog_category_flat';
    const CATALOG_PRODUCT_FLAT        = 'catalog_product_flat';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_FASTINDEXER_ENABLE) === 1;
    }
}