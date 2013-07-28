<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableCreator extends Varien_Object
{
    const FINDEX_TBL_PREFIX = 'afstidex_';

    /**
     * @var array
     */
    protected $_createdTables = NULL;

    /**
     * @var Mage_Core_Model_Resource
     */
    protected $_resource = null;
    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @var string
     */
    protected $_originalTableName = '';

    /**
     * @var string
     */
    protected $_tempTableName = '';

    /**
     * @param Varien_Event_Observer $event
     *
     * @return bool
     */
    public function createTable(Varien_Event_Observer $event)
    {
        if (!$this->_runsOnCommandLine() || !Mage::helper('schumacherfm_fastindexer')->isEnabled()) { // run only in shell
            return TRUE;
        }

        /** @var Mage_Core_Model_Resource _resource */
        $this->_resource = $event->getEvent()->getResource();
        /** @var Varien_Db_Adapter_Pdo_Mysql _connection */
        $this->_connection = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

        $this->_setOriginalTableName($event->getEvent()->getTableName(), $event->getEvent()->getTableSuffix());

        if ($this->_isIndexTable() || $this->_isFlatTable()) {
            $this->_setTempTableName()->_createTempTable();
        }
    }

    /**
     * @param string $table
     * @param string $tableSuffix
     *
     * @return $this
     */
    protected function _setOriginalTableName($table, $tableSuffix = '')
    {
        $this->_originalTableName = $table;
        if (!empty($tableSuffix)) {
            $this->_originalTableName .= '_' . $tableSuffix;
        }
        $this->_originalTableName = str_replace(self::FINDEX_TBL_PREFIX, '', $this->_originalTableName);
        return $this;
    }

    /**
     * @return bool
     */
    protected function _runsOnCommandLine()
    {
        return isset($_SERVER['argv']) && (int)$_SERVER['argc'] > 0;
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function _createTempTable()
    {
        if(empty($this->_tempTableName)){
            throw new Exception('fastindexer: $this->_tempTableName is empty!');
        }

        if ($this->_isIndexTable() && !$this->_existsTempTableInDb()) {
            $this->_connection->raw_query('CREATE TABLE `' . $this->_tempTableName . '` like `' . $this->_originalTableName . '`');
        }

        $this->_resource->setMappedTableName($this->_originalTableName, $this->_tempTableName);
        $this->_createdTables[$this->_tempTableName] = $this->_originalTableName;
        return $this;
    }

    /**
     * @return bool
     */
    protected function _existsTempTableInDb()
    {
        if ($this->_createdTables === null) {
            $this->_createdTables = array();
            /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
            $stmt   = $this->_connection->query('SHOW TABLES LIKE \'' . self::FINDEX_TBL_PREFIX . '%\'');
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tables as $table) {
                $tn                        = reset($table);
                $this->_createdTables[$tn] = str_replace(self::FINDEX_TBL_PREFIX, '', $tn);
            }
        }
        return isset($this->_createdTables[$this->_tempTableName]);
    }

    /**
     * @return $this
     */
    protected function _setTempTableName()
    {
        if (strstr($this->_originalTableName, self::FINDEX_TBL_PREFIX) !== FALSE) {
            $this->_tempTableName = $this->_originalTableName;
        }
        $this->_tempTableName = self::FINDEX_TBL_PREFIX . $this->_originalTableName;
        return $this;
    }

    /**
     * @return bool
     */
    protected function _isIndexTable()
    {
        return
            strpos($this->_originalTableName, '_index') > 2 ||
            strpos($this->_originalTableName, '_idx') > 2 ||
            strstr($this->_originalTableName, 'core_url_rewrite') !== FALSE;
    }

    /**
     * @return bool
     */
    protected function _isFlatTable()
    {
        return
            strstr($this->_originalTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_CATEGORY_FLAT) !== FALSE ||
            strstr($this->_originalTableName, SchumacherFM_FastIndexer_Helper_Data::CATALOG_PRODUCT_FLAT) !== FALSE;
    }

    /**
     * @return array|null
     */
    public function getTables()
    {
        return $this->_createdTables;
    }

    /**
     * unsets to internal table cache to reread the table names again from the DB
     *
     * @return $this
     */
    public function unsetTables()
    {
        $this->_createdTables = null;
        return $this;
    }

}