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
            return null;
        }

        foreach ($tablesToRename as $_originalTableName) {

            if (Mage::helper('schumacherfm_fastindexer')->isFlatTablePrefix($_originalTableName)) {
                continue;
            }
            try {
                $oldTableNewName = $this->_renameTable($_originalTableName);
                //$this->_dropTable($oldTableNewName);

                //$this->_copyCustomUrlRewrites($_originalTableName, $oldTableNewName);

                // reset table names
                $this->_getResource()->setMappedTableName($_originalTableName, $_originalTableName);
            } catch (Exception $e) {
                if (true === $this->_isEchoOn) {
                    echo 'Please see exception log!' . PHP_EOL;
                }
                Mage::logException($e);
            }
        }
        // due to singleton pattern ... reset Tables
        Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->unsetTables();
        return null;
    }

    protected function _renameTable($tableName)
    {
        $tables = array();
        // from shadow db to default db new
        $tables[] = $this->_sqlRenameTo($this->_getShadowDbName(true) . '.' . $this->_quote($tableName), $this->_quote($tableName . '_new'));
        // from default db to shadow db
        $tables[] = $this->_sqlRenameTo($this->_quote($tableName), $this->_getShadowDbName(true) . '.' . $this->_quote($tableName));
        // from default db new to default db
        $tables[] = $this->_sqlRenameTo($this->_quote($tableName. '_new') , $this->_quote($tableName));
        $sql = self::DISABLE_CHECKDDLTRANSACTION . 'RENAME TABLE ' . implode(',', $tables);
        return $this->_rawQuery($sql);
    }

    /**
     * @param string $oldName
     * @param string $newName
     *
     * @return string
     */
    private function _sqlRenameTo($oldName, $newName)
    {
        return $oldName . ' TO ' . $newName;
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
         *
         * SOLUTION but ...
         *
         *
         * create table `core_url_rewrite_bkp` like `core_url_rewrite`;
         * insert into `core_url_rewrite_bkp` select * from `core_url_rewrite`;
         * truncate table `core_url_rewrite`;
         * -- getting custom rewrites resp. redirects
         * insert into `core_url_rewrite` (`store_id` ,  `id_path` ,  `request_path` ,  `target_path` ,  `is_system` ,  `options` ,  `description` ,
         * `category_id` ,  `product_id` )
         * SELECT `store_id` ,  `id_path` ,  `request_path` ,  `target_path` ,  `is_system` ,  `options` ,  `description` ,  `category_id` ,
         * `product_id`  FROM `core_url_rewrite_bkp` where is_system=0 and id_path not like '%|_%' ESCAPE '|' order by `store_id`,`id_path`;
         * select 'now reindex the rewrites';

         */
        return true;

        /**
         * this query could be the solution:
         * delete FROM `core_url_rewrite` WHERE is_system =0 AND id_path RLIKE  '[0-9]+_[0-9]+'
         * these entries are rewrites for old names
         */

        if (strstr($this->_getResource()->getTableName('core/url_rewrite'), $currentTableName) === false) {
            return false;
        }
        $columns = $this->_getColumnsFromTable($currentTableName);

        // maybe use insertFromSelect() ...
        $this->_getConnection()->query('INSERT INTO `' . $currentTableName . '` (' . implode(',', $columns) . ')
            SELECT ' . implode(',', $columns) . ' FROM `' . $oldExistingTable . '` WHERE `is_system`=0');
        return true;
    }
}