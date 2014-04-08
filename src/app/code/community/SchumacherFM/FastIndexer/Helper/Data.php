<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * path in the app/etc/local.xml to the current database name
     */
    const CONFIG_DB_NAME = 'global/resources/default_setup/connection/dbname';

    /**
     * @var boolean
     */
    protected $_isPdoFastIndexerInstance = null;

    /**
     * @var boolean
     */
    protected $_isEnabled = null;

    /**
     * @var string
     */
    protected $_shadowDbName = null;

    /**
     * @var string
     */
    protected $_currentDbName = null;

    /**
     * @var Mage_Core_Model_Config
     */
    protected $_config = null;

    /**
     * @var Mage_Core_Model_Store
     */
    protected $_store = null;

    /**
     * @param Mage_Core_Model_Config $config
     * @param Mage_Core_Model_Store  $store
     */
    public function __construct(Mage_Core_Model_Config $config = null, Mage_Core_Model_Store $store = null)
    {
        $this->_config = $config;
        $this->_store  = $store;
    }

    /**
     * @return Mage_Core_Model_Config
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_config = Mage::getConfig();
        }
        return $this->_config;
    }

    /**
     * due to phpunit ... because when running phpunit the event resource_get_tablename
     * is fired before the stores/websites has been initialized so then Mage::app()->getStore() will fail
     * due to not finding any stores.
     * My model mock_store is a temp work around until a the phpunit event phpunit_suite_start_after has been
     * fired to notify this helper that the stores has been loaded
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        if (null === $this->_store) {
            try {
                $this->_store = Mage::app()->getStore();
            } catch (Mage_Core_Model_Store_Exception $e) {
                $this->_store = Mage::getModel('schumacherfm_fastindexer/mock_store');
            }
        }
        return $this->_store;
    }

    /**
     * notifier
     * @fire phpunit_suite_start_after
     */
    public function reinitHelper()
    {
        $this->_store                    = null;
        $this->_isEnabled                = null;
        $this->_shadowDbName             = null;
        $this->_currentDbName            = null;
        $this->_isPdoFastIndexerInstance = null;
    }

    /**
     * @param int $index
     *
     * @return mixed
     */
    public function getShadowDbName($index = 1)
    {
        if (null === $this->_shadowDbName) {
            $this->_shadowDbName = $this->getStore()->getConfig('system/fastindexer/dbName' . $index);
            if (empty($this->_shadowDbName)) {
                Mage::throwException('Shadow DB Name cannot be empty!');
            }
        }
        return $this->_shadowDbName;
    }

    /**
     * @return string
     */
    public function getDefaultSetupDbName()
    {
        if (null === $this->_currentDbName) {
            $this->_currentDbName = (string)$this->getConfig()->getNode(self::CONFIG_DB_NAME);
            if (empty($this->_currentDbName)) {
                Mage::throwException('Current DB Name cannot be empty!');
            }
        }
        return $this->_currentDbName;
    }

    /**
     * This method will be executed each time resource_get_tablename is called and that is pretty often.
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (null === $this->_isEnabled) {
            $enabled          = (int)$this->getStore()->getConfig('system/fastindexer/is_active') === 1;
            $this->_isEnabled = $this->isPdoFastIndexerInstance() && true === $enabled;
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
    public function optimizeTables()
    {
        return Mage::getStoreConfigFlag('system/fastindexer/optimizeTables');
    }

    /**
     * @return bool
     */
    public function enableUrlRewriteCopyCustom()
    {
        return Mage::getStoreConfigFlag('system/fastindexer/urlRewriteCopyCustom');
    }
}