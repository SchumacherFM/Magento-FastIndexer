<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Lock_Db implements SchumacherFM_FastIndexer_Model_Lock_LockInterface
{
    /**
     * @var string
     */
    protected $_indexerCode = null;

    /**
     * @var SchumacherFM_FastIndexer_Model_Resource_Lock_Db
     */
    protected $_resource = null;

    /**
     * @return SchumacherFM_FastIndexer_Model_Resource_Lock_Db
     */
    public function getResource()
    {
        if (null !== $this->_resource) {
            return $this->_resource;
        }
        $this->_resource = Mage::getResourceSingleton('schumacherfm_fastindexer/lock_db');
        return $this->_resource;
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     */
    public function lock()
    {
        $this->getResource()->startLock($this->getIndexerCode());
    }

    /**
     * Lock and block process.
     * If new instance of the process will try validate locking state
     * script will wait until process will be unlocked
     *
     * @return Mage_Index_Model_Process
     */
    public function lockAndBlock()
    {
        $this->getResource()->startLock($this->getIndexerCode());
    }

    /**
     * Unlock process
     *
     */
    public function unlock()
    {
        $this->getResource()->endLock($this->getIndexerCode());
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->getResource()->isLocked($this->getIndexerCode());
    }

    /**
     * @param string $indexerCode
     *
     * @return $this
     */
    public function setIndexerCode($indexerCode)
    {
        $this->_indexerCode = $indexerCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndexerCode()
    {
        return $this->_indexerCode;
    }
}