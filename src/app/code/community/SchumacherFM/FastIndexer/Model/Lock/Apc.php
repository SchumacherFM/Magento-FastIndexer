<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Lock_Apc implements SchumacherFM_FastIndexer_Model_Lock_LockInterface
{
    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     * @return Mage_Index_Model_Process
     */
    public function lock()
    {
        // TODO: Implement lock() method.
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
        // TODO: Implement lockAndBlock() method.
    }

    /**
     * Unlock process
     *
     * @return Mage_Index_Model_Process
     */
    public function unlock()
    {
        // TODO: Implement unlock() method.
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        // TODO: Implement isLocked() method.
    }
}