<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
interface SchumacherFM_FastIndexer_Model_Lock_LockInterface
{

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     * @return Mage_Index_Model_Process
     */
    public function lock();

    /**
     * Lock and block process.
     * If new instance of the process will try validate locking state
     * script will wait until process will be unlocked
     *
     * @return Mage_Index_Model_Process
     */
    public function lockAndBlock();

    /**
     * Unlock process
     *
     * @return Mage_Index_Model_Process
     */
    public function unlock();

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked();
}