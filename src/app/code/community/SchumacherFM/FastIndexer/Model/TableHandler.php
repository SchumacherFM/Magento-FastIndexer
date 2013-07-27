<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.schumacher.fm)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableHandler extends Varien_Object
{

    const EVENT_PREFIX = 'after_reindex_process_';

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    protected $_doDropTable = TRUE;

    protected $_processName = '';

    protected $_enableEcho = TRUE;

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = $this->_getResource()->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        }
        return $this->_connection;
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    protected function _setProcessName($name)
    {
        $name               = str_replace(self::EVENT_PREFIX, '', $name);
        $this->_processName = $name;
    }

    /**
     * @param Varien_Event_Observer $event
     */
    public function renameTables(Varien_Event_Observer $event)
    {
        $this->_setProcessName($event->getEvent()->getName());

        $tablesToRename = Mage::getSingleton('findex/fastIndexer')->getTables();

        foreach ($tablesToRename as $newTable => $currentTableName) {

            $oldExistingTable = $this->_renameTable($currentTableName, $newTable);

            if ($this->_enableEcho) {
                echo $this->_formatLine($oldExistingTable, $this->_getTableCount($currentTableName));
                echo $this->_formatLine($currentTableName, $this->_getTableCount($currentTableName));
                flush();
            }
            $this->_dropTable($oldExistingTable);

            // reset table names
            $this->_getResource()->setMappedTableName($currentTableName, $currentTableName);
        }
        Mage::getSingleton('findex/fastIndexer')->unsetTables();
    }

    /**
     * That's the magic ;-)
     *
     * @param string $existingTable
     * @param string $newTable
     *
     * @return string
     */
    protected function _renameTable($existingTable, $newTable)
    {
        $oldExistingTable = $existingTable;
        $oldExistingTable .= $this->_doDropTable === TRUE
            ? '_old'
            : date('mdHi');

        $tables = array(
            $this->_sqlRenameTo($existingTable, $oldExistingTable),
            $this->_sqlRenameTo($newTable, $existingTable),
        );
        $this->_getConnection()->query('RENAME TABLE ' . implode(',', $tables));
        return $oldExistingTable;
    }

    /**
     * @param string $tableName
     * @param int    $counter
     *
     * @return string
     */
    protected function _formatLine($tableName, $counter)
    {
        return str_pad($tableName, 50, '_', STR_PAD_RIGHT) . ' ' . $counter . PHP_EOL;
    }

    /**
     * @param string $oldName
     * @param string $newName
     *
     * @return string
     */
    protected function  _sqlRenameTo($oldName, $newName)
    {
        return '`' . $oldName . '` TO `' . $newName . '`';
    }

    /**
     * @param $tableName
     *
     * @return int
     */
    protected function _getTableCount($tableName)
    {
        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt    = $this->_getConnection()->query('SELECT COUNT(*) as counted from `' . $tableName . '`');
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
        if ($this->_doDropTable === TRUE) {
            $this->_getConnection()->dropTable($tableName);
        }
    }

    /**
     * Get Indexer instance
     *
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('index/indexer');
    }
}