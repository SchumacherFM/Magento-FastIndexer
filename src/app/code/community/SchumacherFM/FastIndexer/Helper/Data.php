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
    const CONFIG_DB_NAME = 'global/resources/default_setup/connection/dbname';

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
        return Mage::getStoreConfigFlag('system/fastindexer/enable');
    }

    /**
     * @return bool
     */
    public function dropOldTable()
    {
        return Mage::getStoreConfigFlag('system/fastindexer/dropOldTable');
    }

    /**
     * @return bool
     */
    public function isEcho()
    {
        return Mage::getStoreConfigFlag('system/fastindexer/echo');
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