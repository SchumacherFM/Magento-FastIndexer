<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Table prefix for flat tables
     */
    const CATALOG_CATEGORY_FLAT = 'catalog_category_flat';
    const CATALOG_PRODUCT_FLAT  = 'catalog_product_flat';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (int)Mage::getStoreConfig('schumacherfm/fastindexer/enable') === 1;
    }

    /**
     * @return bool
     */
    public function dropOldTable()
    {
        return (int)Mage::getStoreConfig('schumacherfm/fastindexer/dropOldTable') === 1;
    }

    /**
     * @return bool
     */
    public function isEcho()
    {
        return (int)Mage::getStoreConfig('schumacherfm/fastindexer/echo') === 1;
    }

    /**
     * @param string $currentTableName
     *
     * @return bool
     */
    public function isFlatTablePrefix($currentTableName)
    {
        return
            $currentTableName === self::CATALOG_CATEGORY_FLAT ||
            $currentTableName === self::CATALOG_PRODUCT_FLAT;
    }
}