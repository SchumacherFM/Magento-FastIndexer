<?php

/**
 * @author kiri
 * @date   5/3/14
 */
class SchumacherFM_FastIndexer_Adminhtml_FastindexerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        /** @var Mage_Admin_Model_Session $session */
        $session = Mage::getSingleton('admin/session');
        $result  = $session->isAllowed('index/fastindexer');
        return $result;
    }

    /**
     * Mass action to remove a lock from a indexer process
     */
    public function massRemoveLockAction()
    {
        $data = $this->getRequest()->getPost('process');
        if (false === empty($data) && is_array($data)) {
            try {
                /** @var SchumacherFM_FastIndexer_Model_Index_Process $process */
                $process = Mage::getModel('index/process');
                /*  Mage_Index_Model_Resource_Process_Collection $processCollection */
                $processCollection = $process->getCollection()->load()->getItems();
                $results           = [];
                foreach ($data as $id) {
                    $id = (int)$id;
                    if (isset($processCollection[$id])) {
                        /** @var SchumacherFM_FastIndexer_Model_Index_Process $aProcess */
                        $aProcess = $processCollection[$id];
                        $code     = $aProcess->getIndexerCode();
                        /** @var SchumacherFM_FastIndexer_Model_Lock_LockInterface $lockInstance */
                        $lockInstance = $process->getLockInstance();
                        $lockInstance->setIndexerCode($code)->setIndexerId($id);
                        if (true === $lockInstance->isLocked()) {
                            $lockInstance->unlock();
                            $aProcess->setStatus(Mage_Index_Model_Process::STATUS_PENDING);
                            $aProcess->save();
                            $results[] = $code;
                        }
                        $process->setLockInstance(null); // because Singleton
                    }
                }
                if (0 === count($results)) {
                    $results[] = $this->__('No indexer have been unlocked!');
                }
                $this->_getSession()->addSuccess(
                    $this->__('Unlocked Indexer: %s', implode(', ', $results))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                Mage::logException($e);
            }
        }
        $this->_redirect('*/process/list');
    }

    public function scheduleReindexAction()
    {
        $this->_addIndexerToSchedule(true);
        $this->_redirect('*/process/list');
    }

    public function schedulePartialAction()
    {
        $this->_addIndexerToSchedule(false);
        $this->_redirect('*/process/list');
    }

    protected function _addIndexerToSchedule($isFull = true)
    {
        $helper    = Mage::helper('schumacherfm_fastindexer');
        $name      = true === $isFull ? 'Full Reindex' : 'Partial Reindex';
        $processId = (int)$this->getRequest()->getParam('process', 0);
        try {
            /** @var SchumacherFM_FastIndexer_Model_Cron $cron */
            $cron = Mage::getModel('schumacherfm_fastindexer/cron');
            $cron
                ->setProcessId($processId)
                ->setFullReindex(true === $isFull)
                ->addIndexerProcessToCronSchedule();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $helper->__('%s: Successfully added %s to cron scheduler.', $name, $cron->getIndexerCodeByProcessId())
            );
        } catch (InvalidArgumentException $e) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('%s: %s', $name, $e->getMessage()));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($helper->__('%s: Failed to add Process %s to scheduler', $name, $processId));
        }
    }
}