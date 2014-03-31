<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Block_Adminhtml_System_Config_Setup extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('schumacherfm/findexer/system/config/setup.phtml');
        return $this;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return bool
     */
    public function isInstanceOfFindexerPdo()
    {
        return Mage::helper('schumacherfm_fastindexer')->isPdoFastIndexerInstance();
    }
}
