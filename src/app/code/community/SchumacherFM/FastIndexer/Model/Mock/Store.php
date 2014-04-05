<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   private!
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Mock_Store
{

    public function getConfig($path)
    {
        $mapping = array(
            'system/fastindexer/dbName1' => 'mock_test',
            'system/fastindexer/dbName2' => 'mock_test2',
            'system/fastindexer/enable'  => 0,
        );
        return isset($mapping[$path]) ? $mapping[$path] : null;
    }

    /**
     * due to phpunit ... because when running phpunit the event resource_get_tablename
     * is fired before the stores/websites has been initialized so then Mage::app()->getStore() will fail
     * due to not finding any stores
     * @fire phpunit_suite_start_after
     *
     */
    public function notifyHelperThatStoreIsReady()
    {
        Mage::helper('schumacherfm_fastindexer')->reinitHelper();
    }
}