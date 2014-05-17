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
}