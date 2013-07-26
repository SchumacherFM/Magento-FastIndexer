<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) 2012 SchumacherFM AG (http://www.unic.com)
 * @author    @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_FastIndexer extends Varien_Object
{
    protected $_createdTables = array();

    public function changeTableName(Varien_Event_Observer $event)
    {

        /** @var Mage_Core_Model_Resource $resource */
        $resource    = $event->getEvent()->getResource();
        $tableName   = $event->getEvent()->getTableName();
        $modelEntity = $event->getEvent()->getModelEntity();

        if (strpos($tableName, 'index') > 2 || strpos($tableName, 'idx') > 2) {

            $tn = $this->_getTableName($tableName);
            $resource->setMappedTableName($tableName, $tn);
            if (!isset($this->_createdTables[$tn])) {
                $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE)
                    ->query('CREATE TABLE `' . $tn . '` like `' . $tableName . '`'
                    );

                $this->_createdTables[$tn] = $tableName;
            }
        }

    }

    protected function _getTableName($tableName)
    {
        return 'aFastIndex_' . $tableName;
    }

    public function getTables()
    {
        return $this->_createdTables;
    }

    public function renameTablesAfterIndexing(Varien_Event_Observer $event)
    {

    }
}