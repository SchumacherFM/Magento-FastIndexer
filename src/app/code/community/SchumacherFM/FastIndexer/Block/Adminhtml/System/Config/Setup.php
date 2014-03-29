<?php

class SchumacherFM_FastIndexer_Block_Adminhtml_System_Config_Setup extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->_connection = Mage::getSingleton('core/resource')->getConnection(Mage_Core_Model_Resource::DEFAULT_SETUP_RESOURCE);
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
        return   $this->_toHtml();
    }

    public function isInstanceOfFindexerPdo()
    {
        return $this->_connection instanceof SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql;
    }
}
