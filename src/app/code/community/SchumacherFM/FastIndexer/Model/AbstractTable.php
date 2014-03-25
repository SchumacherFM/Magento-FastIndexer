<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
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
     * @var array
     */
    protected $_connection = array();

    /**
     * @deprecated
     * @var boolean
     */
    protected $_shadowResourceCreated = null;

    protected $_isEchoOn = false;
    protected $_shadowDbName = array();
    protected $_currentDbName = null;

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
     * @param string $type
     *
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getConnection($type = null)
    {
        $type = null === $type ? Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE : $type;
        if (false === isset($this->_connection[$type])) {
            $this->_connection[$type] = $this->_getResource()->getConnection($type);
        }
        return $this->_connection[$type];
    }

    /**
     * @param Mage_Core_Model_Resource $resource
     *
     * @return $this
     */
    protected function _setResource(Mage_Core_Model_Resource $resource)
    {
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
     * @param $tableName
     *
     * @return int
     */
    protected function _getTableCount($tableName)
    {
        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt    = $this->_getConnection()->query('SELECT COUNT(*) AS counted FROM ' . $this->_getTableName($tableName));
        $counter = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return isset($counter[0]) ? (int)$counter[0]['counted'] : 0;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    protected function _dropTable($tableName)
    {
        $this->_rawQuery('DROP TABLE IF EXISTS ' . $this->_getTableName($tableName));
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function _getTableName($tableName)
    {
        return $this->_quote($tableName);
    }

    /**
     * @param string $sql
     *
     * @return Zend_Db_Statement_Interface
     */
    protected function _rawQuery($sql)
    {
        if (true === $this->_isEchoOn) {
            Mage::log($sql, null, 'findexer.log');
        }
        return $this->_getConnection()->raw_query($sql);
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
     * When the default indexer of the flat table runs. it drops first the flat table and then creates it new.
     * used connection_name: catalog_write
     *
     * @return bool
     */
    protected function _isFlatTable()
    {
        return Mage::helper('schumacherfm_fastindexer')->isFlatTablePrefix($this->_currentTableName);
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
    protected function _initShadowResourcePdoModel()
    {
        if ($this->_shadowResourceCreated === null) {

            $types = array(
                'catalog_write',
                Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE
            );

            foreach ($types as $type) {
                $nodePrefix     = 'global/resources/' . $type . '/connection';
                $connectionNode = Mage::getConfig()->getNode($nodePrefix);

                if ($connectionNode && !isset($connectionNode->use)) {
                    $connectDefault = Mage::getConfig()->getResourceConnectionConfig($type);
                } else {
                    $connectDefault = Mage::getConfig()->getResourceConnectionConfig(Mage_Core_Model_Resource::DEFAULT_SETUP_RESOURCE);
                }

                $connectDefault->type = 'pdo_mysql_findexer';
                foreach ($connectDefault->asArray() as $nodeName => $nodeValue) {
                    Mage::getConfig()->setNode($nodePrefix . '/' . $nodeName, $nodeValue);
                }
            }
            $this->_shadowResourceCreated = true;
        }
        return $this->_shadowResourceCreated;
    }
}