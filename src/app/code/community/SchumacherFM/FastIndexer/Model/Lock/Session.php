<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Lock_Session
    extends SchumacherFM_FastIndexer_Model_Lock_Abstract
    implements SchumacherFM_FastIndexer_Model_Lock_LockInterface
{
    const SESS_PREFIX = 'fastindexer_';

    /**
     * @var Mage_Core_Model_Resource_Session
     */
    protected $_session = null;

    /**
     * @return Mage_Core_Model_Resource_Session
     */
    public function getSession()
    {
        if (null !== $this->_session) {
            return $this->_session;
        }
        $this->_session = Mage::getResourceSingleton('core/session');
        return $this->_session;
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     */
    public function lock()
    {
        $this->getSession()->write(self::SESS_PREFIX . $this->getIndexerCode(), microtime(true));
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
        $this->getSession()->destroy(self::SESS_PREFIX . $this->getIndexerCode());
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        $startTime = (double)$this->getSession()->read(self::SESS_PREFIX . $this->getIndexerCode());
        if ($startTime < 0.0001) {
            return false;
        }
        return $this->_isLockedByTtl($startTime);
    }
}