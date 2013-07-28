<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_TableRollback extends Varien_Object
{

    const EVENT_PREFIX = 'after_reindex_process_';

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    protected $_isEchoOn = FALSE;

    /**
     * @param Varien_Event_Observer $event
     *
     * @return bool
     */
    public function rollbackTables(Varien_Event_Observer $event = null)
    {
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
                $this->_restoreTableKeys($_originalTableName, $oldTableNewName);

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

    /**
     * restores the original name of the foreign key
     *
     * @param string $_originalTableName
     * @param string $oldOriginalTable
     *
     * @return $this
     */
    protected function _restoreTableKeys($_originalTableName, $oldOriginalTable)
    {
        $_originalFks = $this->_getConnection()->getForeignKeys($_originalTableName);

        if (count($_originalFks) > 0) {
            // because key name contains: FINDEX_TBL_PREFIX
            // drop and create of a FK only possible in ONE statement = RENAME
            $sqlFk = array();
            foreach ($_originalFks as $_fk) {
                if ($this->_isEchoOn === TRUE) {
                    echo 'Drop FK: ' . $_originalTableName . ' -> ' . $_fk['FK_NAME'] . PHP_EOL;
                }
                $sqlFk[] = 'DROP FOREIGN KEY ' . $this->_quote($_fk['FK_NAME']);

                $originalFkName = $this->_removeTablePrefix($_fk['FK_NAME']);
                if ($this->_isEchoOn === TRUE) {
                    echo 'Add FK: ' . $_originalTableName . ' -> ' . $originalFkName . PHP_EOL;
                }

                $query = sprintf('ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
                    $this->_quote($originalFkName),
                    $this->_quote($_fk['COLUMN_NAME']),
                    $this->_quote($_fk['REF_TABLE_NAME']),
                    $this->_quote($_fk['REF_COLUMN_NAME'])
                );

                if (!empty($_fk['ON_DELETE'])) {
                    $query .= ' ON DELETE ' . strtoupper($_fk['ON_DELETE']);
                }
                if (!empty($_fk['ON_UPDATE'])) {
                    $query .= ' ON UPDATE ' . strtoupper($_fk['ON_UPDATE']);
                }

                $sqlFk[] = $query;
            }
            $sql = '/*disable*/ ALTER TABLE ' . $this->_quote($_originalTableName) . ' ' . implode(',', $sqlFk);
            $this->_getConnection()->raw_query($sql);
        }

        $_originalIndexList = $this->_getIndexList($_originalTableName);

        if (count($_originalIndexList) > 0) {
            // drop and create of an index only possible in ONE statement = RENAME
            $sqlIndex = array();
            foreach ($_originalIndexList as $_key) {
                if ($this->_isEchoOn === TRUE) {
                    echo 'Drop IDX: ' . $_originalTableName . ' -> ' . $_key['KEY_NAME'] . PHP_EOL;
                }
                $sqlIndex[] = 'DROP INDEX ' . $this->_quote($_key['KEY_NAME']);

                $originalIdxName = $this->_removeTablePrefix($_key['KEY_NAME']);
                if ($this->_isEchoOn === TRUE) {
                    echo 'Add IDX: ' . $_originalTableName . ' -> ' . $originalIdxName . PHP_EOL;
                }

                switch (strtolower($_key['INDEX_TYPE'])) {
                    case Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE:
                        $condition = 'UNIQUE ' . $this->_quote($originalIdxName);
                        break;
                    case Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT:
                        $condition = 'FULLTEXT ' . $this->_quote($originalIdxName);
                        break;
                    default:
                        $condition = 'INDEX ' . $this->_quote($originalIdxName);
                        break;
                }

                foreach ($_key['COLUMNS_LIST'] as $k => $v) {
                    $_key['COLUMNS_LIST'][$k] = $this->_quote($v);
                }
                $sqlIndex[] = sprintf('ADD %s (%s)', $condition, implode(',', $_key['COLUMNS_LIST']));
            }
            $sql = '/*disable*/ ALTER TABLE ' . $this->_quote($_originalTableName) . ' ' . implode(',', $sqlIndex);
            $this->_getConnection()->raw_query($sql);
        }

        return $this;
    }

    /**
     * index names are always upper case
     *
     * @param $string
     *
     * @return mixed
     */
    protected function _removeTablePrefix($string)
    {
        return str_replace(strtoupper(SchumacherFM_FastIndexer_Model_TableCreator::FINDEX_TBL_PREFIX), '', $string);
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
     * @param string $tableName
     *
     * @return array
     */
    protected function _getIndexList($tableName)
    {
        $index = $this->_getConnection()->getIndexList($tableName);
        if (isset($index['PRIMARY'])) {
            unset($index['PRIMARY']);
        }
        return $index;
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
     * returns all non PRI columns
     *
     * @param $tableName
     *
     * @return array
     */
    protected function _getColumnsFromTable($tableName)
    {
        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt          = $this->_getConnection()->query('SHOW COLUMNS FROM `' . $tableName . '`');
        $columnsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columns       = array();
        foreach ($columnsResult as $col) {
            if ($col['Key'] !== 'PRI') {
                $columns[] = '`' . $col['Field'] . '`';
            }
        }
        return $columns;
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
        if (Mage::helper('schumacherfm_fastindexer')->dropOldTable() === TRUE) {
            $this->_getConnection()->dropTable($tableName);
        }
    }

}