<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Lock_Shmop
    extends SchumacherFM_FastIndexer_Model_Lock_Abstract
    implements SchumacherFM_FastIndexer_Model_Lock_LockInterface
{

    const PERM = 0666;
    const LEN  = 512;

    /**
     * @var boolean
     */
    protected $_isLocked = null;

    /**
     * @var null
     */
    protected $_shmId = null;

    /**
     * On any *nix use the commands ipcs and ipcrm to work with the memory
     *
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     */
    public function lock()
    {
        $shmId = shmop_open($this->getIndexerId(), 'n', self::PERM, self::LEN);
        shmop_write($shmId, $this->getIndexerId(), 0);
        shmop_close($shmId);
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
        $shmId = shmop_open($this->getIndexerId(), 'w', self::PERM, self::LEN);
        shmop_write($shmId, 0, 0); // cannot delete because sometimes we're not the owner. so overwrite
        shmop_close($shmId);
        $this->_isLocked = false;
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if (0 === $this->getIndexerId()) {
            Mage::throwException('FastIndexer: IndexerId cannot be 0');
        }

        if (null !== $this->_isLocked) {
            return $this->_isLocked;
        }
        $shmId = @shmop_open($this->getIndexerId(), 'a', self::PERM, self::LEN);
        if (false === $shmId) {
            $this->_isLocked = false;
            return $this->_isLocked;
        }
        $size = shmop_size($shmId);
        $data = (int)shmop_read($shmId, 0, $size);
        shmop_close($shmId);
        $this->_isLocked = $data === $this->getIndexerId();
        return $this->_isLocked;
    }
}