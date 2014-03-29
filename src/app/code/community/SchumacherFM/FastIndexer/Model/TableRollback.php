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

        $tablesToRename = Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->getTables();
        if (empty($tablesToRename)) {
            return null;
        }
        $this->_setResource(Mage::getSingleton('core/resource'));

        foreach ($tablesToRename as $_originalTableData) {
            if (true === $this->_isProductFlatTable($_originalTableData['t'])) {
                foreach ($this->_getStoreIds() as $storeId) {
                    $_originalTableData['s'] = $storeId;
                    $this->_renameShadowTables($_originalTableData);
                }
                $_originalTableData['s'] = null;
            } else {
                $this->_renameShadowTables($_originalTableData);
            }

            // reset table names ... if necessary
            $this->_getResource()->setMappedTableName($_originalTableData['t'], $_originalTableData['t']);
        }
        // due to singleton pattern ... reset Tables ... not needed?
        Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->unsetTables();
        return null;
    }

    /**
     * @param array $_originalTableData
     *
     * @return null
     */
    protected function _renameShadowTables(array $_originalTableData)
    {
        $this->_copyCustomUrlRewrites($_originalTableData);
        $oldTable = $this->_renameTable($_originalTableData);
        $this->_handleOldShadowTable($oldTable);
        return null;
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

        /**
         * especially when in the core db a flat table is non existent then in the shadow db we also do not have
         * an old table.
         */
        if (false === $this->_getConnection()->isTableExists($tableName, $this->_getShadowDbName(false, 1))) {
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
     * @return string
     */
    protected function _renameTable(array $tableData)
    {
        $tableName = $tableData['t'] . (empty($tableData['s']) ? '' : '_' . $tableData['s']);
        $oldTable  = 'z' . date('Ymd_His') . '_' . $tableName;
        $tables    = array();

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
        $this->_sqlRenameRunQuery($tables);
        return $oldTable;
    }

    /**
     * @param array $renames
     *
     * @return Varien_Db_Statement_Pdo_Mysql
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
     * Trying to get all custom URLs and if enabled, all system generated redirect permanents
     *
     * @param array $_originalTableData
     *
     * @return bool
     */
    protected function _copyCustomUrlRewrites(array $_originalTableData)
    {
        if (false === $this->_isUrlRewriteTable($_originalTableData['t'])) {
            return false;
        }

        $allColumns = $this->_getConnection()->describeTable($_originalTableData['t'], $this->_getShadowDbName(false, 1));
        unset($allColumns['url_rewrite_id']); // normally check array key PRIMARY for true ;-)
        $columns = array_keys($allColumns);

        // these are all custom URLs entered by the store owner
        $customUrls = array(
            '((category_id IS NOT NULL OR product_id IS NOT NULL) AND options IS NULL)',
            '(category_id IS NULL AND product_id IS NULL AND options IS NOT NULL)',
            '(category_id IS NULL AND product_id IS NULL AND options IS NULL)',
        );

        /**
         * these are the redirect permanents generated by the system.
         * enable this and no new generated URL will be added anymore and the bug is fixed.
         *
         */
        if (true === Mage::helper('schumacherfm_fastindexer')->enableUrlRewriteCopySystemRP()) {
            $customUrls[] = '((category_id IS NOT NULL OR product_id IS NOT NULL) AND options =\'RP\')';
        }

        $select = 'SELECT ' . implode(',', $columns) . ' FROM `' . $_originalTableData['t'] . '`
            WHERE is_system=0 AND (' . implode(' OR ', $customUrls) . ')';

        $this->_rawQuery('INSERT INTO ' . $this->_getShadowDbName(true, 1) . '.' . $_originalTableData['t'] . ' (' . implode(',', $columns) . ')' . $select);

        return true;
    }
}