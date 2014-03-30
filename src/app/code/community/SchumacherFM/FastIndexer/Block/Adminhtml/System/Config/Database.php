<?php

class SchumacherFM_FastIndexer_Block_Adminhtml_System_Config_Database extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * @var Varien_Data_Form_Element_Text
     */
    protected $_currentElement = null;

    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->_connection = Mage::getSingleton('core/resource')->getConnection(Mage_Core_Model_Resource::DEFAULT_SETUP_RESOURCE);
        parent::_prepareLayout();
        $this->setTemplate('schumacherfm/findexer/system/config/database.phtml');
        return $this;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_currentElement = $element;
        return parent::render($element);
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        Mage::register($element->getHtmlId(), $element->getValue());
        $parent = parent::_getElementHtml($element);
        return $parent . $this->_toHtml();
    }

    /**
     * @return bool
     */
    public function getDatabaseExists()
    {
        if (strpos($this->_currentElement->getHtmlId(), 'dbName2') !== false) {
            $value1 = Mage::registry('system_fastindexer_dbName1');
            if (strtolower($this->_currentElement->getValue()) === strtolower($value1)) {
                return false;
            }
        }

        $dbName = $this->_currentElement->getData('value');
        if (empty($dbName)) {
            return false;
        }
        $result = $this->_connection->fetchOne('SELECT SCHEMA_NAME FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE SCHEMA_NAME=:db', array('db' => $dbName));
        return $result !== false;
    }
}
