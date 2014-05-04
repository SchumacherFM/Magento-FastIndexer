<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Lock_Semaphore
    extends SchumacherFM_FastIndexer_Model_Lock_Abstract
    implements SchumacherFM_FastIndexer_Model_Lock_LockInterface
{

    /**
     * @var boolean
     */
    protected $_isLocked = null;

    /**
     * @var resource
     */
    protected $_semId = null;

    /**
     * @var null
     */
    protected $_shmId = null;

    /**
     * @return string
     */
    public function getIndexerCodeCrc()
    {
        return sprintf('%u', crc32($this->getIndexerCode()));
    }

    /**
     * @return resource
     */
    protected function _getSemIdentifier()
    {
        if (null !== $this->_semId) {
            return $this->_semId;
        }
        $this->_semId = sem_get($this->getIndexerCodeCrc(), 512, 0666);
        if (false === $this->_semId) {
            Mage::throwException('FastIndexer: Cannot create semaphore id lock for ' . $this->getIndexerCode());
        }
        $this->_shmId = shm_attach($this->getIndexerCodeCrc(), 64);
        return $this->_semId;
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     */
    public function lock()
    {
        $success = sem_acquire($this->_getSemIdentifier());
        shm_put_var($this->_shmId, $this->getIndexerCodeCrc(), 1);

        if (false === $success) {
            Mage::throwException('FastIndexer: Cannot acquire semaphore lock!');
        }
        $this->_isLocked = true;
    }

    /**
     * Lock and block process.
     * If new instance of the process will try validate locking state
     * script will wait until process will be unlocked
     *
     */
    public function lockAndBlock()
    {
        $this->lock();
    }

    /**
     * Unlock process
     *
     * @return Mage_Index_Model_Process
     */
    public function unlock()
    {
        $this->_getSemIdentifier();

        shm_remove_var($this->_shmId, $this->getIndexerCodeCrc());
        @sem_release($this->_getSemIdentifier());
        $this->_isLocked = false;
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if (null !== $this->_isLocked) {
            return $this->_isLocked;
        }
        $this->_getSemIdentifier();
        return shm_has_var($this->_shmId, $this->getIndexerCodeCrc());
    }

    /**
     * Close resource if it was opened
     */
    public function __destruct()
    {
        if ($this->_shmId) {
            shm_detach($this->_shmId);
        }
    }
}