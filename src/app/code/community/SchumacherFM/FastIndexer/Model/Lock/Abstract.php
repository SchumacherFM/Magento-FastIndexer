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
}