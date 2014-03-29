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
     * path in the app/etc/local.xml to the current database name
     */
    const CONFIG_DB_NAME = 'global/resources/default_setup/connection/dbname';

    /**
     * Table prefix for flat tables
     */
    const CATALOG_CATEGORY_FLAT = 'catalog_category_flat';

    /**
     * @var boolean
     */
    protected $_isPdoFastIndexerInstance = null;

    /**
     * @var boolean
     */
    protected $_isEnabled = null;

    /**
     * This method will be executed each time resource_get_tablename is called and that is pretty often.
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (null === $this->_isEnabled) {
            $this->_isEnabled = $this->isPdoFastIndexerInstance() && Mage::getStoreConfigFlag('system/fastindexer/enable');
        }
        return $this->_isEnabled;
    }

    /**
     * @return bool
     */
    public function isPdoFastIndexerInstance()
    {
        if (null === $this->_isPdoFastIndexerInstance) {
            $connection                      = Mage::getSingleton('core/resource')->getConnection(Mage_Core_Model_Resource::DEFAULT_SETUP_RESOURCE);
            $this->_isPdoFastIndexerInstance = $connection instanceof SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql;
        }
        return $this->_isPdoFastIndexerInstance;
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
    public function enableUrlRewriteCopySystemRP()
    {
        return Mage::getStoreConfigFlag('system/fastindexer/urlRewriteCopySystemRP');
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
            $currentTableName === Mage_Catalog_Model_Product_Flat_Indexer::ENTITY;
    }
}