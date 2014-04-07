<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
abstract class SchumacherFM_FastIndexer_Model_AbstractTable
{
    /**
     * @see Varien_Db_Adapter_Pdo_Mysql::_checkDdlTransaction
     * I found that string under my foot nails.
     */
    const DISABLE_CHECKDDLTRANSACTION = '/*disable _checkDdlTransaction*/ ';

    /**
     * @var Mage_Core_Model_Resource
     */
    protected $_resource = null;

    /**
     * @var SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @deprecated
     * @var boolean
     */
    protected $_shadowResourceCreated = null;

    protected $_shadowDbName = array();
    protected $_currentDbName = null;

    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_helper = null;

    public function __construct($helper = null)
    {
        if (false === empty($helper) && $helper instanceof SchumacherFM_FastIndexer_Helper_Data) {
            $this->_helper = $helper;
        }
    }

    /**
     * @return \SchumacherFM_FastIndexer_Helper_Data
     */
    public function getHelper()
    {
        if (null === $this->_helper) {
            $this->_helper = Mage::helper('schumacherfm_fastindexer');
        }
        return $this->_helper;
    }

    /**
     * @param bool $quote
     *
     * @return string
     */
    protected function _getCurrentDbName($quote = false)
    {
        if (null === $this->_currentDbName) {
            $this->_currentDbName = (string)Mage::getConfig()->getNode(SchumacherFM_FastIndexer_Helper_Data::CONFIG_DB_NAME);
            if (empty($this->_currentDbName)) {
                Mage::throwException('Current DB Name cannot be empty!');
            }
        }
        if (true === $quote) {
            return $this->_quote($this->_currentDbName);
        }
        return $this->_currentDbName;
    }

    /**
     * @param bool $quote
     * @param int  $index
     *
     * @return string
     */
    protected function _getShadowDbName($quote = false, $index = 1)
    {
        if (false === isset($this->_shadowDbName[$index])) {
            $this->_shadowDbName[$index] = trim(Mage::getStoreConfig('system/fastindexer/dbName' . $index));
            if (empty($this->_shadowDbName[$index])) {
                Mage::throwException('Shadow DB Name cannot be empty!');
            }
        }
        if (true === $quote) {
            return $this->_quote($this->_shadowDbName[$index]);
        }
        return $this->_shadowDbName[$index];
    }

    /**
     * @return SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_getResource()->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        }
        return $this->_connection;
    }

    /**
     * @param Mage_Core_Model_Resource $resource
     *
     * @return $this
     */
    public function setResource($resource = null)
    {
        if (null === $this->_resource && null !== $resource) {
            $this->_resource = $resource;
        } elseif (null === $this->_resource) {
            $this->_resource = Mage::getSingleton('core/resource');
        }
        $this->_resource = $resource;
        return $this;
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return $this->_resource;
    }

    /**
     * @param string $tableName
     * @param string $schema
     *
     * @return bool
     */
    protected function _dropTable($tableName, $schema = '')
    {
        $schema = empty($schema) ? '' : $this->_quote($schema) . '.';
        $this->_rawQuery('DROP TABLE IF EXISTS ' . $schema . $this->_quote($tableName));
    }

    /**
     * @param string $sql
     *
     * @return Zend_Db_Statement_Interface
     */
    protected function _rawQuery($sql)
    {
        $sql = self::DISABLE_CHECKDDLTRANSACTION . $sql;
        try {
            return $this->_getConnection()->raw_query($sql);
        } catch (PDOException $e) {
            Mage::log(PHP_EOL . $sql . PHP_EOL . $e->__toString(), Zend_Log::ERR, 'fastIndexerException.log');
        }
        return false;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function _quote($string)
    {
        return $this->_getConnection()->quoteIdentifier($string);
    }

    /**
     * Creates a new config node for e.g. catalog_write resource so that the indexer will use a different PDO model
     * because flat indexer uses the table name as prefix for the index name and when there is a table name like
     * test.catalog_product_flat ... the index creation process will fail.
     * There also many other bugs in Varien_Db_Adapter_Pdo_Mysql which are fixed in our PDO model.
     * It is important this method will be executed with the retrieval of the first table name because in the
     * Mage_Catalog_Model_Resource_Product_Flat_Indexer::prepareFlatTable() is the "bug" that first the PDO adapter
     * will be fetched and then the table name. More ideal is the other way round :-)
     *
     * Product/Flat/Indexer.php is not using the catalog_read resource, it is using DEFAULT_READ_RESOURCE,
     * so we also must patch that xml node :-(
     *
     * We have to change in etc/local.xml the type to pdo_mysql_findexer because the product flat indexer
     * uses the already instantiated connection core_read which we cannot change anymore with an event observer :-(
     * Damn!
     *
     * @deprecated and only listed here for doc reasons.
     *
     * @return boolean
     */
//    protected function _initShadowResourcePdoModel()
//    {
//        if ($this->_shadowResourceCreated === null) {
//
//            $types = array(
//                'catalog_write',
//                Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE
//            );
//
//            foreach ($types as $type) {
//                $nodePrefix     = 'global/resources/' . $type . '/connection';
//                $connectionNode = Mage::getConfig()->getNode($nodePrefix);
//
//                if ($connectionNode && !isset($connectionNode->use)) {
//                    $connectDefault = Mage::getConfig()->getResourceConnectionConfig($type);
//                } else {
//                    $connectDefault = Mage::getConfig()->getResourceConnectionConfig(Mage_Core_Model_Resource::DEFAULT_SETUP_RESOURCE);
//                }
//
//                $connectDefault->type = 'pdo_mysql_findexer';
//                foreach ($connectDefault->asArray() as $nodeName => $nodeValue) {
//                    Mage::getConfig()->setNode($nodePrefix . '/' . $nodeName, $nodeValue);
//                }
//            }
//            $this->_shadowResourceCreated = true;
//        }
//        return $this->_shadowResourceCreated;
//    }
}