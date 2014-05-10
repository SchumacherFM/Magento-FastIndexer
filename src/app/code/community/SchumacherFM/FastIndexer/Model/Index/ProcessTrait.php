<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
trait SchumacherFM_FastIndexer_Model_Index_ProcessTrait
{

    /**
     * @var SchumacherFM_FastIndexer_Model_Lock_LockInterface
     */
    protected $_lockInstance = null;

    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_helper = null;

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    public function getFiHelper()
    {
        if (null === $this->_helper) {
            $this->_helper = Mage::helper('schumacherfm_fastindexer');
        }
        return $this->_helper;
    }

    /**
     * @param SchumacherFM_FastIndexer_Model_Lock_LockInterface $lockInstance
     *
     * @return $this
     */
    public function setLockInstance($lockInstance)
    {
        $this->_lockInstance = $lockInstance;
        return $this;
    }

    /**
     * @return bool|SchumacherFM_FastIndexer_Model_Lock_LockInterface
     */
    public function getLockInstance()
    {
        if (null !== $this->_lockInstance) {
            return $this->_lockInstance;
        }

        $userModel = Mage::getStoreConfig('fastindexer/indexer/lock_model');
        if (true === empty($userModel) || false === $this->getFiHelper()->isEnabled()) {
            $this->_lockInstance = false;
            return false;
        }
        $this->_lockInstance = Mage::getModel('schumacherfm_fastindexer/lock_' . $userModel);
        $this->_lockInstance->setIndexerCode($this->getIndexerCode())->setIndexerId($this->getId());
        return $this->_lockInstance;
    }

    /**
     * @return $this
     */
    public function reindexAll()
    {
        if ($this->isLocked()) {
            Mage::throwException(Mage::helper('schumacherfm_fastindexer')->__(
                '%s Index process is working now. Please try run this process later or remove the lock if no indexer is running!',
                $this->getIndexer()->getName()));
        }
        Mage::dispatchEvent('fastindexer_before_reindex_process_' . $this->getIndexerCode());
        return parent::reindexAll();
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process runing and fast lock validation.
     *
     * @return Mage_Index_Model_Process
     */
    public function lock()
    {
        if (false === $this->getLockInstance()) {
            return parent::lock();
        }
        $this->getLockInstance()->lock();
        return $this;
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
        if (false === $this->getLockInstance()) {
            return parent::lockAndBlock();
        }
        $this->getLockInstance()->lockAndBlock();
        return $this;
    }

    /**
     * Unlock process
     *
     * @return Mage_Index_Model_Process
     */
    public function unlock()
    {
        if (false === $this->getLockInstance()) {
            return parent::unlock();
        }
        $this->getLockInstance()->unlock();
        return $this;
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if (false === $this->getLockInstance()) {
            return parent::isLocked();
        }
        return $this->getLockInstance()->isLocked();
    }
}
