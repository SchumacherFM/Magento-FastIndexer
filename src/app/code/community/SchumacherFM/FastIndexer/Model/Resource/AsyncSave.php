<?php

class SchumacherFM_FastIndexer_Model_Resource_AsyncSave extends Mage_Core_Model_Resource_Abstract
{

    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = true;

    /**
     * Use is object new method for save of object
     *
     * @var boolean
     */
    protected $_useIsObjectNew = false;

    /**
     * @var array
     */
    protected $_serializableFields = array();

    /**
     * Main table primary key field name
     *
     * @var string
     */
    protected $_idFieldName;

    /**
     * Fields List for update in forsedSave
     *
     * @var array
     */
    protected $_fieldsForUpdate = array();

    /**
     * @var Mage_Core_Model_Resource_Db_Abstract
     */
    protected $_objectResource = null;

    /**
     * @var SchumacherFM_FastIndexer_Model_Db_Adapter_Mysqli
     */
    protected $_writeAdapter = null;

    protected $_lastAsyncResult = null;

    protected function _construct()
    {
        if (false === defined('MYSQLI_ASYNC')) {
            Mage::throwException('Ups ... MYSQLI_ASYNC is not available for you ... :-( http://php.net/mysqli_query');
        }
    }

    /**
     * not available
     */
    final protected function _getReadAdapter()
    {
        Mage::throwException('Ups ... this is a write only resource class.');
    }

    /**
     * @return SchumacherFM_FastIndexer_Model_Db_Adapter_Mysqli
     */
    final protected function _getWriteAdapter()
    {
        if (null !== $this->_writeAdapter) {
            return $this->_writeAdapter;
        }
        $this->_writeAdapter = new SchumacherFM_FastIndexer_Model_Db_Adapter_Mysqli($this->_getCoreReadAdapter()->getConfig());
        return $this->_writeAdapter;
    }

    /**
     * @return SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    protected function _getCoreReadAdapter()
    {
        return Mage::getResourceSingleton('core/resource')->getReadConnection();
    }

    /**
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _getObjectResource(Mage_Core_Model_Abstract $object)
    {
        if (null === $this->_objectResource) {
            if ($object->getResource() instanceof Mage_Core_Model_Resource_Db_Abstract) {
                $this->_objectResource = $object->getResource();
            } else {
                Mage::throwException('Object resource must be an instance of Mage_Core_Model_Resource_Db_Abstract');
            }
        }
        return $this->_objectResource;
    }

    /**
     * @param string $idFieldName
     *
     * @return $this
     */
    public function setIdFieldName($idFieldName)
    {
        $this->_idFieldName = $idFieldName;
        return $this;
    }

    /**
     * Get primary key field name
     *
     * @return string
     */
    public function getIdFieldName()
    {
        if (empty($this->_idFieldName)) {
            Mage::throwException(Mage::helper('core')->__('Empty identifier field name'));
        }
        return $this->_idFieldName;
    }

    /**
     * Returns the last result and clears it
     *
     * @return null|bool|mysqli_result
     */
    public function getLastAsyncResult()
    {
        $result                 = $this->_lastAsyncResult;
        $this->_lastAsyncResult = null;
        return $result;
    }

    /**
     * Save object object data
     *
     * @param Mage_Core_Model_Abstract $object
     * @param array                    $_fieldsForUpdate
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function save(Mage_Core_Model_Abstract $object, array $_fieldsForUpdate = null)
    {
        $this->setIdFieldName($object->getIdFieldName());
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        if (null !== $_fieldsForUpdate) {
            $this->_fieldsForUpdate = $_fieldsForUpdate;
        }

        $this->_beforeSave($object);
        $this->_serializeFields($object);

        $bind = $this->_prepareDataForSave($object);
        if (!is_null($object->getId()) && $this->_isPkAutoIncrement) {
            unset($bind[$this->getIdFieldName()]);
            $condition              = $this->_getCoreReadAdapter()->quoteInto($this->getIdFieldName() . '=?', $object->getId());
            $this->_lastAsyncResult = $this->_getWriteAdapter()->update($this->_getObjectResource($object)->getMainTable(), $bind, $condition);
        } else {
            $this->_lastAsyncResult = $this->_getWriteAdapter()
                ->insertOnDuplicate($this->_getObjectResource($object)->getMainTable(), $bind, $this->_fieldsForUpdate);
        }
        $this->_afterSave($object);
        return $this;
    }

    /**
     * @param Mage_Core_Model_Abstract $object
     *
     * @return $this
     */
    public function delete(Mage_Core_Model_Abstract $object)
    {
        $this->setIdFieldName($object->getIdFieldName());
        $this->_beforeDelete($object);
        $this->_getWriteAdapter()->delete(
            $this->_getObjectResource($object)->getMainTable(),
            $this->_getCoreReadAdapter()->quoteInto($this->getIdFieldName() . '=?', $object->getId())
        );
        $this->_afterDelete($object);
        return $this;
    }

    /**
     * @todo see _prepareDataForTable
     * Prepare data for save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return array
     */
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        return $object->getData();
        // return $this->_prepareDataForTable($object, $this->getMainTable());
    }

    /**
     * @param array $serializableFields
     *
     * @return $this
     */
    public function setSerializableFields(array $serializableFields)
    {
        $this->_serializableFields = $serializableFields;
        return $this;
    }

    /**
     * Serialize serializeable fields of the object
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _serializeFields(Mage_Core_Model_Abstract $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_serializeField($object, $field, $serializeDefault, isset($parameters[2]));
        }
    }

    /**
     * Perform actions before object save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object delete
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object delete
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }
}
