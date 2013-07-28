<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableRollback extends SchumacherFM_FastIndexer_Model_AbstractTable
{

    /**
     * @param Varien_Event_Observer $event
     *
     * @return bool
     */
    public function rollbackTables(Varien_Event_Observer $event = null)
    {
        $this->_setResource(Mage::getSingleton('core/resource'));

        $this->_isEchoOn = Mage::helper('schumacherfm_fastindexer')->isEcho();
        $tablesToRename  = Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->getTables();

        if (empty($tablesToRename)) {
            return FALSE;
        }

        foreach ($tablesToRename as $_tempTableName => $_originalTableName) {

            if (Mage::helper('schumacherfm_fastindexer')->isFlatTablePrefix($_originalTableName)) {
                continue;
            }
            try {
                $oldTableNewName = $this->_renameTable($_originalTableName, $_tempTableName);
                $this->_restoreTableKeys($_originalTableName);

                if ($this->_isEchoOn === TRUE) {
                    echo $this->_formatLine($oldTableNewName, $this->_getTableCount($_originalTableName));
                    echo $this->_formatLine($_originalTableName, $this->_getTableCount($_originalTableName));
                    flush();
                }
                $this->_copyCustomUrlRewrites($_originalTableName, $oldTableNewName);
                $this->_dropTable($oldTableNewName);

                // reset table names
                $this->_getResource()->setMappedTableName($_originalTableName, $_originalTableName);
            } catch (Exception $e) {
                echo 'Please see exception log!' . PHP_EOL;
                Mage::logException($e);
            }
        }
        // due to singleton pattern ... reset Tables
        Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->unsetTables();
    }

    /**
     * @param $currentTableName
     * @param $oldExistingTable
     *
     * @return bool 248265
     */
    protected function _copyCustomUrlRewrites($currentTableName, $oldExistingTable)
    {
        /**
         * seems there is a strange thing finding the custom rewrites
         * now the custom rewrites will be lost ...
         */
        return TRUE;

        /**
         * this query could be the solution:
         * delete FROM `core_url_rewrite` WHERE is_system =0 AND id_path RLIKE  '[0-9]+_[0-9]+'
         * these entries are rewrites for old names
         */

        if (strstr($this->_getResource()->getTableName('core/url_rewrite'), $currentTableName) === FALSE) {
            return FALSE;
        }
        $columns = $this->_getColumnsFromTable($currentTableName);

        // maybe use insertFromSelect() ...
        $this->_getConnection()->query('INSERT INTO `' . $currentTableName . '` (' . implode(',', $columns) . ')
            SELECT ' . implode(',', $columns) . ' FROM `' . $oldExistingTable . '` WHERE `is_system`=0');
        return TRUE;
    }

    /**
     * That's the magic ;-)
     *
     * @param string $oldTable
     * @param string $newTable
     *
     * @return string
     */
    protected function _renameTable($oldTable, $newTable)
    {
        $oldTableNewName = $oldTable;
        $oldTableNewName .= Mage::helper('schumacherfm_fastindexer')->dropOldTable() === TRUE
            ? '_old'
            : date('mdHi');

        $tables = array();
        if ($this->_getConnection()->isTableExists($oldTable)) {
            $tables[] = $this->_sqlRenameTo($oldTable, $oldTableNewName);
        }
        $tables[] = $this->_sqlRenameTo($newTable, $oldTable);

        $sql = '/*disable _checkDdlTransaction*/ RENAME TABLE ' . implode(',', $tables);
        $this->_getConnection()->raw_query($sql);
        return $oldTableNewName;
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

}