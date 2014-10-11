<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Block_Adminhtml_Index_Notifications extends Mage_Index_Block_Adminhtml_Notifications
{
    /**
     * As the indexer now runs async and manual we don't need anymore the notifications
     *
     * @return array
     */
    public function getProcessesForReindex()
    {
        return false;
    }

    /**
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }
}
