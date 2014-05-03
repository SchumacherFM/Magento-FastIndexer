<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_System_Config_Lock
{
    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_helper;

    /**
     * @param SchumacherFM_FastIndexer_Helper_Data $helper
     */
    public function __construct($helper = null)
    {
        if ($helper) {
            $this->_helper = $helper;
        }
    }

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    public function getHelper()
    {
        if (!$this->_helper) {
            // @codeCoverageIgnoreStart
            $this->_helper = Mage::helper('schumacherfm_fastindexer');
        }
        // @codeCoverageIgnoreEnd
        return $this->_helper;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = $this->getHelper();
        return array(
            array('value' => '', 'label' => $helper->__('Default')),
            array('value' => 'semaphore', 'label' => $helper->__('Semaphore')),
            array('value' => 'db', 'label' => $helper->__('Database')),
            array('value' => 'session', 'label' => $helper->__('Session')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toOptionHash()
    {
        $options = array();
        foreach ($this->toOptionArray() as $option) {
            $key           = $option['value'];
            $options[$key] = $option['label'];
        }
        return $options;
    }
}