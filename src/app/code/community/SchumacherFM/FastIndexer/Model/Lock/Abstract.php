<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
abstract class SchumacherFM_FastIndexer_Model_Lock_Abstract
{
    /**
     * @var string
     */
    protected $_indexerCode = null;

    /**
     * @var int
     */
    protected $_indexerId = 0;

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

    /**
     * @param int $indexerId
     *
     * @return $this
     */
    public function setIndexerId($indexerId)
    {
        $this->_indexerId = (int)$indexerId;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndexerId()
    {
        return $this->_indexerId;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return Mage::helper('schumacherfm_fastindexer')->getLockThreshold();
    }

    /**
     * @param double $startTime
     *
     * @return bool
     */
    protected function _isLockedByTtl($startTime)
    {
        $now = microtime(true);
        return ($startTime + $this->getTtl()) > $now;
    }

    /**
     * @return string
     */
    protected function _getMicrotimeString()
    {
        return (string)microtime(true);
    }

    /**
     * @return int
     */
    protected function _getMicrotimeLen()
    {
        return strlen($this->_getMicrotimeString());
    }
}