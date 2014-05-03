<?php

/**
 * @author kiri
 * @date   4/15/14
 */
class SchumacherFM_FastIndexer_Model_Resource_Lock_Db extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize  table and table pk
     *
     */
    protected function _construct()
    {
        $this->_init('schumacherfm_fastindexer/lock', 'indexer_code');
    }
//
//    /**
//     * Update process/event association row status
//     *
//     * @param int    $processId
//     * @param int    $eventId
//     * @param string $status
//     *
//     * @return Mage_Index_Model_Resource_Process
//     */
//    public function updateEventStatus($processId, $eventId, $status)
//    {
//        $adapter   = $this->_getWriteAdapter();
//        $condition = array(
//            'indexer_code = ?' => $processId,
//            'event_id = ?'   => $eventId
//        );
//        $adapter->update($this->getTable('schumacherfm_fastindexer/lock'), array('status' => $status), $condition);
//        return $this;
//    }

    /**
     * Register process end
     *
     * @param string $indexerCode
     *
     * @return Mage_Index_Model_Resource_Process
     */
    public function endLock($indexerCode)
    {
        $data = array(
            'ended_at' => microtime(true),
            'locked'   => 0,
        );
        $this->_updateLockData($indexerCode, $data);
        return $this;
    }

    /**
     * @param string $indexerCode
     *
     * @return $this
     */
    public function startLock($indexerCode)
    {
        $t    = microtime(true);
        $data = array(
            'started_at' => $t,
            'ended_at'   => $t,
            'locked'     => 1,
        );
        $this->_updateLockData($indexerCode, $data);
        return $this;
    }

    /**
     * Updates process data
     *
     * @param string $indexerCode
     * @param array  $data
     *
     * @return Mage_Index_Model_Resource_Process
     */
    protected function _updateLockData($indexerCode, $data)
    {
        $fields               = array_keys($data);
        $data['indexer_code'] = $indexerCode;
        $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $data, $fields);

        return $this;
    }

    /**
     * Update process start date
     *
     * @param string $indexerCode
     *
     * @return boolean
     */
    public function isLocked($indexerCode)
    {
        $bind = array(
            'ic' => $indexerCode
        );
        /** @var SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql $adapter */
        $adapter = $this->_getReadAdapter();

        $result = $adapter->fetchRow('SELECT indexer_code,started_at,ended_at,locked FROM ' .
            $this->getMainTable() . ' WHERE `indexer_code`=:ic', $bind);

        $isLocked = (int)$result['locked'] === 1;

        /**
         * now detect any broken previous runs where the indexer failed and died
         * this can't be done easily but we could rely on the average runtime of the last index process
         * plus a threshold
         */
        if (1 === $isLocked) {
            $now          = microtime(true);
            $sa           = (double)$result['started_at'];
            $ea           = (double)$result['ended_at'];
            $lastDuration = $ea - $sa;
            $threshold    = Mage::helper('schumacherfm_fastindexer')->getLockThreshold();
            $lastDuration = $lastDuration < 0.1 ? $threshold : $lastDuration;
            /**
             * unlock a locked indexer
             */
            if (($sa + $lastDuration + $threshold) < $now) {
                Mage::log('FastIndexer: Unlocking locked indexer ' . $indexerCode);
                $isLocked = false;
            }
        }

        return $isLocked;
    }
}