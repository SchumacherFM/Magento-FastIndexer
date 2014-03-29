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
        if (false === $this->_runsOnCommandLine() || false === Mage::helper('schumacherfm_fastindexer')->isEnabled()) {
            return null;
        }

        $this->_stores = Mage::app()->getStores();
        $this->_setResource(Mage::getSingleton('core/resource'));

        $tablesToRename  = Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->getTables();
        if (empty($tablesToRename)) {
            return null;
        }

        foreach ($tablesToRename as $_originalTableData) {

            try {

                if (true === $this->_isProductFlatTable($_originalTableData['t'])) {
                    foreach ($this->_stores as $store) {
                        /** @var Mage_Core_Model_Store $store */
                        $_originalTableData['s'] = $store->getId();
                        $this->_renameShadowTables($_originalTableData);
                    }
                    $_originalTableData['s'] = null;
                } else {
                    $this->_renameShadowTables($_originalTableData);
                }

                // reset table names ... if necessary
                $this->_getResource()->setMappedTableName($_originalTableData['t'], $_originalTableData['t']);
                //$this->_copyCustomUrlRewrites($_originalTableName, $oldTableNewName);

            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        // due to singleton pattern ... reset Tables
        Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->unsetTables();
        return null;
    }

    protected function _renameShadowTables(array $_originalTableData)
    {
        $result = $this->_renameTable($_originalTableData);
        $this->_handleOldShadowTable($result['oldName']);
    }

    /**
     * Either remove the old table in the shadow DB1 or remove all indexes and FK from shadow db1 because FK
     * and Index names must be unique in the whole DB
     *
     * @param string $tableName
     *
     * @return null
     */
    protected function _handleOldShadowTable($tableName)
    {
        if (true === Mage::helper('schumacherfm_fastindexer')->dropOldTable()) {
            $this->_rawQuery(self::DISABLE_CHECKDDLTRANSACTION .
                'DROP TABLE IF EXISTS ' . $this->_getShadowDbName(true) . '.' . $this->_quote($tableName));
            return null;
        }

        $operations = array(
            'getForeignKeys' => 'dropForeignKey',
            'getIndexList'   => 'dropIndex',
        );

        foreach ($operations as $get => $drop) {
            $keyList = $this->_getConnection()->$get($tableName, $this->_getShadowDbName(false, 1));
            if (count($keyList) === 0) {
                continue;
            }
            if (isset($keyList['PRIMARY'])) {
                unset($keyList['PRIMARY']);
            }
            foreach ($keyList as $key) {
                $this->_getConnection()->$drop(
                    $tableName,
                    isset($key['KEY_NAME']) ? $key['KEY_NAME'] : $key['FK_NAME'],
                    $this->_getShadowDbName(false, 1)
                );
            }
        }

        return null;
    }

    /**
     * @param array $tableData / two keys: t = table name; s = suffix
     *
     * @return array
     */
    protected function _renameTable(array $tableData)
    {
        $tableName = $tableData['t'] . (empty($tableData['s']) ? '' : '_' . $tableData['s']);
        $oldTable  = 'z' . date('Ymd_His') . '_' . $tableName;
        $tables    = array();
        $return    = array();

        // if in the default DB the table will not exists, simply rename without any other movings.
        if (false === $this->_getConnection()->isTableExists($tableName)) {
            $tables[] = $this->_sqlRenameTo($this->_getShadowDbName(true, 1) . '.' . $this->_quote($tableName), $this->_quote($tableName));
        } else {
            $tables[] = $this->_sqlRenameTo($this->_quote($tableName), $this->_getShadowDbName(true, 2) . '.' . $this->_quote($oldTable));
            $tables[] = $this->_sqlRenameTo($this->_getShadowDbName(true, 1) . '.' . $this->_quote($tableName), $this->_quote($tableName));
            $tables[] = $this->_sqlRenameTo(
                $this->_getShadowDbName(true, 2) . '.' . $this->_quote($oldTable),
                $this->_getShadowDbName(true) . '.' . $this->_quote($oldTable)
            );
        }
        $return['rename']  = $this->_sqlRenameRunQuery($tables);
        $return['oldName'] = $oldTable;

        return $return;
    }

    /**
     * @param array $renames
     *
     * @return Zend_Db_Statement_Interface
     */
    private function _sqlRenameRunQuery(array $renames)
    {
        $sql = self::DISABLE_CHECKDDLTRANSACTION . 'RENAME TABLE ' . implode(',', $renames);
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