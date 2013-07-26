<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.unic.com)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableHandler
{
    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    protected $_doDropTable = TRUE;

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = Mage::getSingleton('core/resource')->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        }
        return $this->_connection;
    }

    public function renameTables(Varien_Event_Observer $event)
    {
        $tablesToRename = Mage::getSingleton('findex/fastIndexer')->getTables();

        foreach ($tablesToRename as $newTable => $existingTable) {
            $oldExistingTable = $existingTable . '_old';

            $tables = array(
                $this->_sqlRenameTo($existingTable, $oldExistingTable),
                $this->_sqlRenameTo($newTable, $existingTable),
            );
            $this->_getConnection()->query('RENAME TABLE ' . implode(',', $tables));

            echo $this->_formatLine($oldExistingTable, $this->_getTableCount($existingTable));
            echo $this->_formatLine($existingTable, $this->_getTableCount($existingTable));
            $this->_dropTable($oldExistingTable);
        }

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
        if ($this->_doDropTable !== TRUE) {
            return FALSE;
        }
        $this->_getConnection()->dropTable($tableName);
        return TRUE;
    }

}