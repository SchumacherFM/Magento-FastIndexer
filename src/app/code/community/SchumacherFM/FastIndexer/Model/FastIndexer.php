<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.schumacher.fm)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_FastIndexer extends Varien_Object
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
    protected $_currentTableName = '';

    protected function _runsOnCommandLine()
    {
        return isset($_SERVER['argv']) && (int)$_SERVER['argc'] > 0;
    }

    public function changeTableName(Varien_Event_Observer $event)
    {
        if (!$this->_runsOnCommandLine()) { // run only in shell
            return TRUE;
        }

        /** @var Mage_Core_Model_Resource _resource */
        $this->_resource = $event->getEvent()->getResource();
        /** @var Varien_Db_Adapter_Pdo_Mysql _connection */
        $this->_connection       = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $this->_currentTableName = $event->getEvent()->getTableName();

        if ($this->_isIndexTable()) {
            $newTableName = $this->_getNewTableName();
            $this->_resource->setMappedTableName($this->_currentTableName, $newTableName);
            $this->_createTable($newTableName);

        }
        return TRUE;
    }

    /**
     * @param string $newTableName
     *
     * @return bool
     */
    protected function _createTable($newTableName)
    {
        if ($this->_existsNewTableInDb($newTableName)) {
            $this->_connection->query('TRUNCATE `' . $newTableName);
        } else {
            $this->_connection->query('CREATE TABLE `' . $newTableName . '` like `' . $this->_currentTableName . '`');
            $this->_createdTables[$newTableName] = $this->_currentTableName;
        }
        return TRUE;
    }

    /**
     * @param string $newTableName
     *
     * @return bool
     */
    protected function _existsNewTableInDb($newTableName)
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
        return isset($this->_createdTables[$newTableName]);
    }

    /**
     * @return string
     */
    protected function _getNewTableName()
    {
        return self::FINDEX_TBL_PREFIX . $this->_currentTableName;
    }

    /**
     * @return bool
     */
    protected function _isIndexTable()
    {
        return strpos($this->_currentTableName, '_index') > 2 ||
        strpos($this->_currentTableName, '_idx') > 2 ||
        strpos($this->_currentTableName, '_flat') > 2; // @todo bug does not work with flat structure
    }

    public function getTables()
    {
        return $this->_createdTables;
    }

}