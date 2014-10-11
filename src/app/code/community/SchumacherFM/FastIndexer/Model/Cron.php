<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */

/**
 * @method SchumacherFM_FastIndexer_Model_Cron setProcessId(int $value)
 * @method SchumacherFM_FastIndexer_Model_Cron setFullReindex(bool $value)
 * Class SchumacherFM_FastIndexer_Model_Cron
 */
class SchumacherFM_FastIndexer_Model_Cron extends Varien_Object
{
    const JOB_CODE = 'schumacherfm_fastindexer_cron';

    /**
     * @var string
     */
    protected $_dateTime = null;

    /**
     * @var string
     */
    protected $_indexerCode = null;

    /**
     * @var SchumacherFM_FastIndexer_Helper_Data
     */
    protected $_helper = null;

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assignes it as object attributes
     * This behaviour may change in child classes
     *
     */
    public function __construct($helper = null)
    {
        if (false === empty($helper) && $helper instanceof SchumacherFM_FastIndexer_Helper_Data) {
            $this->_helper = $helper;
        }

        $this->_initOldFieldsMap();
        if ($this->_oldFieldsMap) {
            $this->_prepareSyncFieldsMap();
        }

        $args = func_get_args();
        if ((null === $this->_helper && empty($args[0])) || !isset($args[1])) {
            $args[1] = array();
        }
        $this->_data = $args[1];
        $this->_addFullNames();
        $this->_construct();
    }

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    public function getHelper()
    {
        if (null === $this->_helper) {
            $this->_helper = Mage::helper('schumacherfm_fastindexer');
        }
        return $this->_helper;
    }

    public function addIndexerProcessToCronSchedule()
    {
        /** @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');
        $schedule->setJobCode(self::JOB_CODE);
        $schedule->setCreatedAt($this->_getCurrentDateTime());
        $schedule->setMessages(json_encode(array(
            "ic" => $this->getIndexerCodeByProcessId(), // ic indexer code
            "fr" => (bool)$this->getFullReindex(), // fr full reindex
        )));
        $schedule->setScheduledAt($this->_getCurrentDateTime());
        $schedule->save();
        return $this;
    }

    /**
     * @param bool $reInit
     *
     * @return string
     */
    protected function _getCurrentDateTime($reInit = false)
    {
        if (null === $this->_dateTime || true == $reInit) {
            $this->_dateTime = date('Y-m-d H:i:s');
        }
        return $this->_dateTime;
    }

    /**
     * @param int $processId null
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function getIndexerCodeByProcessId($processId = null)
    {
        if (null === $this->_indexerCode) {
            $processId = (int)(null === $processId ? $this->getData('process_id') : $processId);
            if (0 === $processId) {
                throw new InvalidArgumentException('FastIndexer: ProcessId cannot be 0.');
            }
            $this->_indexerCode = Mage::getModel('index/process')->load($processId)->getIndexerCode();
            if (true === empty($this->_indexerCode)) {
                throw new InvalidArgumentException('FastIndexer: IndexerCode not found by ProcessID: ' . $processId);
            }
        }
        return $this->_indexerCode;
    }

    /**
     * CRON
     * Executes the last queued indexer job which has been added manually
     * @return bool|string
     */
    public function execLastQueuedIndexer()
    {
        /** @var Mage_Cron_Model_Schedule $scheduledJob */
        $scheduledJob = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', self::JOB_CODE)
            ->addFieldToFilter('status', array('in' => array(Mage_Cron_Model_Schedule::STATUS_PENDING, Mage_Cron_Model_Schedule::STATUS_MISSED)))
            ->setOrder('scheduled_at', 'DESC')
            ->getLastItem();

        if ((int)$scheduledJob->getId() < 1) {
            return false;
        }

        $scheduledJob
            ->setExecutedAt($this->_getCurrentDateTime())
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->save();

        $message = json_decode($scheduledJob->getMessages(), true);
        if (!isset($message['ic']) || empty($message['ic'])) {
            return false;
        }
        $indexerCode   = $message['ic'];
        $isFullReindex = true === $message['fr'];

        /** @var SchumacherFM_FastIndexer_Model_Index_Process $indexProcess */
        $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode($indexerCode);

        if ($indexProcess) {
            if (true === $isFullReindex) {
                $indexProcess->reindexEverything();
            } else {
                $this->_execPartialIndex($indexProcess);
            }
        }
        $scheduledJob
            ->setFinishedAt($this->_getCurrentDateTime(true))
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS)
            ->save();

        return $indexerCode;
    }

    /**
     * @param Mage_Index_Model_Process $process
     *
     * @throws Exception
     */
    protected function _execPartialIndex(Mage_Index_Model_Process $process)
    {
        $this->_indexerTransactionBegin();

        // MODE_SCHEDULE available in Mage >1.8
        $indexMode   = true === defined('Mage_Index_Model_Process::MODE_SCHEDULE')
            ? Mage_Index_Model_Process::MODE_SCHEDULE
            : 'schedule';
        $pendingMode = Mage_Index_Model_Process::STATUS_PENDING;

        try {
            $process->setMode($indexMode);
            $process->indexEvents();
            $unProcessedEvents = count(Mage::getResourceSingleton('index/event')->getUnprocessedEvents($process));
            if (0 === $unProcessedEvents) {
                $process->changeStatus($pendingMode);
            }
            $this->_indexerTransactionCommit();
        } catch (Exception $e) {
            $this->_indexerTransactionRollBack();
            throw $e;
        }
    }

    /**
     * Indexes a specific number of events
     *
     * @throws Exception
     */
    public function unprocessed_events_index()
    {
        if (false === $this->getHelper()->isCronAutoIndexEnabled()) {
            return null;
        }
        $this->_indexerTransactionBegin();
        try {
            $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
            /** @var Mage_Index_Model_Process $process */
            foreach ($pCollection as $process) {
                $process->setMode(Mage_Index_Model_Process::MODE_SCHEDULE);
                $eventLimit      = (int)Mage::getStoreConfig('system/asyncindex/event_limit');
                $unprocessedColl = $process->getUnprocessedEventsCollection()->setPageSize($eventLimit);

                /** @var Mage_Index_Model_Event $unprocessedEvent */
                foreach ($unprocessedColl as $unprocessedEvent) {
                    $process->processEvent($unprocessedEvent);
                    $unprocessedEvent->save();
                }
                if (count(Mage::getResourceSingleton('index/event')->getUnprocessedEvents($process)) === 0) {
                    $process->changeStatus(Mage_Index_Model_Process::STATUS_PENDING);
                }
            }
            $this->_indexerTransactionCommit();
        } catch (Exception $e) {
            $this->_indexerTransactionRollBack();
            throw $e;
        }
    }

    protected function _indexerTransactionBegin()
    {
        Mage::getResourceSingleton('index/process')->beginTransaction();
    }

    protected function _indexerTransactionCommit()
    {
        Mage::getResourceSingleton('index/process')->commit();
    }

    protected function _indexerTransactionRollBack()
    {
        Mage::getResourceSingleton('index/process')->rollBack();
    }
}