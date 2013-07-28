<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableCreator
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

        $this->_setOriginalTableName($event->getEvent()->getTableName());

        if ($this->_isIndexTable() || $this->_isFlatTable()) {
            /** @var Mage_Core_Model_Resource _resource */
            $this->_resource = $event->getEvent()->getResource();
            /** @var Varien_Db_Adapter_Pdo_Mysql _connection */
            $this->_connection = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

            $this->_setTempTableName()->_createTempTable($event->getEvent()->getTableSuffix());
        }
    }

    /**
     * @param string $table
     *
     * @return $this
     */
    protected function _setOriginalTableName($table)
    {
        $this->_originalTableName = $table;
        $this->_originalTableName = str_replace(self::FINDEX_TBL_PREFIX, '', $this->_originalTableName);
        return $this;
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
    protected function _runsOnCommandLine()
    {
        return isset($_SERVER['argv']) && (int)$_SERVER['argc'] > 0;
    }

    /**
     * @param string $tableSuffix
     *
     * @return $this
     * @throws Exception
     */
    protected function _createTempTable($tableSuffix = '')
    {
        if (empty($this->_tempTableName)) {
            throw new Exception('fastindexer: $this->_tempTableName is empty!');
        }

        if ($this->_isIndexTable() && !$this->_existsTempTableInDb()) {
            $this->_connection->raw_query(
                '/*disable _checkDdlTransaction*/ CREATE TABLE `' . $this->_tempTableName . '` like `' . $this->_originalTableName . '`'
            );
        }

        $this->_setMapper($this->_originalTableName, $this->_tempTableName);
        // create "virtual" entries ...
        if (!empty($tableSuffix)) {
            $this->_setMapper($this->_originalTableName . '_' . $tableSuffix, $this->_tempTableName . '_' . $tableSuffix);
        }

        return $this;
    }

    /**
     * @param string $_originalTableName
     * @param string $_tempTableName
     */
    protected function _setMapper($_originalTableName, $_tempTableName)
    {
        $this->_resource->setMappedTableName($_originalTableName, $_tempTableName);
        $this->_createdTables[$_tempTableName] = $_originalTableName;
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