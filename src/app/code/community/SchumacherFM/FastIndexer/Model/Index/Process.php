<?php

class SchumacherFM_FastIndexer_Model_Index_Process extends Mage_Index_Model_Process
{

    const BEFORE_REINDEX_PROCESS_EVENT = 'before_reindex_process_';

    public function reindexAll()
    {
        Mage::dispatchEvent(self::BEFORE_REINDEX_PROCESS_EVENT . $this->getIndexerCode());

        parent::reindexAll();
    }
}
