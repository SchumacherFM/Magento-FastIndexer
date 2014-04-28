<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Index_Process extends Mage_Index_Model_Process
{
    const BEFORE_REINDEX_PROCESS_EVENT = 'before_reindex_process_';

    /**
     * @var SchumacherFM_FastIndexer_Model_Lock_LockInterface
     */
    protected $_lockInstance = null;

    /**
     * @return bool|SchumacherFM_FastIndexer_Model_Lock_LockInterface
     */
    protected function _getLockInstance()
    {
        if (null !== $this->_lockInstance) {
            return $this->_lockInstance;
        }

        $userModel = Mage::getStoreConfig('fastindexer/indexer/lock_model');
        if (true === empty($userModel)) {
            $this->_lockInstance = false;
            return false;
        }
        $this->_lockInstance = Mage::getModel('schumacherfm_fastindexer/lock_' . $userModel);
        return $this->_lockInstance;
    }

    public function reindexAll()
    {
        Mage::dispatchEvent(self::BEFORE_REINDEX_PROCESS_EVENT . $this->getIndexerCode());
        parent::reindexAll();
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process runing and fast lock validation.
     *
     * @return Mage_Index_Model_Process
     */
    public function lock()
    {
        if (false === $this->_getLockInstance()) {
            return parent::lock();
        }
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
        $this->_isLocked = true;
        flock($this->_getLockFile(), LOCK_EX);
        return $this;
    }

    /**
     * Unlock process
     *
     * @return Mage_Index_Model_Process
     */
    public function unlock()
    {
        $this->_isLocked = false;
        flock($this->_getLockFile(), LOCK_UN);
        return $this;
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if ($this->_isLocked !== null) {
            return $this->_isLocked;
        } else {
            $fp = $this->_getLockFile();
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                flock($fp, LOCK_UN);
                return false;
            }
            return true;
        }
    }
}
