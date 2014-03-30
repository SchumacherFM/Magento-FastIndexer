<?php

class SchumacherFM_FastIndexer_Model_Index_Process extends Mage_Index_Model_Process
{

    /**
     * Reindex all data what this process responsible is
     *
     */
    public function reindexAll()
    {
//        Mage::dispatchEvent('before_reindex_process_' . $this->getIndexerCode());
        Mage::dispatchEvent('before_reindex_process',
            array('indexer_code' => $this->getIndexerCode())
        );
        parent::reindexAll();
    }
}
