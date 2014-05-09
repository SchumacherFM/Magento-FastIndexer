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
     * @dispatch adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $event
     *
     * @return null
     */
    public function updateIndexerMassAction(Varien_Event_Observer $event = null)
    {
        /** @var Mage_Index_Block_Adminhtml_Process_Grid $block */
        $block = $event->getEvent()->getBlock();
        if (!($block instanceof Mage_Index_Block_Adminhtml_Process_Grid)) {
            return null;
        }

        $block->getMassactionBlock()->addItem('remove_lock', [
            'label' => Mage::helper('schumacherfm_fastindexer')->__('Remove Lock'),
            'url'   => $block->getUrl('*/fastindexer/massRemoveLock'),
        ]);

        return null;
    }
}