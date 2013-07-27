<?php
 /**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   For non commercial use only
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Fast Indexer enabled
     */
    const XML_PATH_FASTINDEXER_ENABLE = 'schumacherfm/fastindexer/enable';

    /**
     * Table prefix for flat tables
     */
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