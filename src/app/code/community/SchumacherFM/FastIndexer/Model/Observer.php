<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Observer
{
    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_helper = null;

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
        if (null !== $this->_helper) {
            return $this->_helper;
        }
        $this->_helper = Mage::helper('schumacherfm_fastindexer');
        return $this->_helper;
    }

    /**
     * @dispatch adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    public function updateIndexerMassAction(Varien_Event_Observer $observer = null)
    {
        /** @var Mage_Index_Block_Adminhtml_Process_Grid $block */
        $block = $observer->getEvent()->getBlock();
        if (!($block instanceof Mage_Index_Block_Adminhtml_Process_Grid)) {
            return null;
        }

        $block->getMassactionBlock()->addItem('remove_lock', array(
            'label' => $this->getHelper()->__('Remove Lock'),
            'url'   => $block->getUrl('*/fastindexer/massRemoveLock'),
        ));

        return null;
    }

    /**
     * Because of PHP Strict when function signatures won't match
     * @fire controller_front_init_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function rewriteCatalogResourceCategoryFlat(Varien_Event_Observer $observer)
    {
        $isMagento19 = version_compare(Mage::getVersion(), '1.9') > 0 ? '19' : '';
        $config      = Mage::getConfig();
        $config->setNode(
            'global/models/catalog_resource/rewrite/category_flat',
            'SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_Flat' . $isMagento19
        );
    }
}