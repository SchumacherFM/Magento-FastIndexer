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
     * Avoid rewrite of Mage_Index_Block_Adminhtml_Process_Grid
     *
     * @dispatch adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    public function updateIndexerGrid(Varien_Event_Observer $observer = null)
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

        $block
            ->removeColumn('action')
            ->addColumn('event_count', array(
                    'header'   => $this->getHelper()->__('Event Count'),
                    'width'    => '80',
                    'index'    => 'event_count',
                    'sortable' => false
                )
            )
            ->addColumn('action', array(
                    'header'    => $this->getHelper()->__('Action'),
                    'width'     => '100',
                    'type'      => 'action',
                    'getter'    => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => $this->getHelper()->__('Reindex Data (Blocking)'),
                            'url'     => array('base' => '*/*/reindexProcess'),
                            'field'   => 'process'
                        ),
                        array(
                            'caption' => $this->getHelper()->__('Schedule Reindex (Non-Blocking)'),
                            'url'     => array('base' => '*/fastindexer/scheduleReindex'),
                            'params'  => array('_current' => true),
                            'field'   => 'process'
                        ),
                        array(
                            'caption' => $this->getHelper()->__('Schedule partial index (Non-Blocking)'),
                            'url'     => array('base' => '*/fastindexer/schedulePartial'),
                            'params'  => array('_current' => true),
                            'field'   => 'process'
                        ),
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'is_system' => true,
                )
            );

        return null;
    }

    /**
     * Avoid rewrite of Mage_Index_Block_Adminhtml_Process_Grid
     *
     * @dispatch process_collection_load_before
     *
     * @param Varien_Event_Observer $observer
     */
    public function addEventCount(Varien_Event_Observer $observer = null)
    {
        /** @var Mage_Index_Model_Resource_Process_Collection $collection */
        $collection = $observer->getEvent()->getProcessCollection();
        // assume that we are in admin/process/list
        if (1 === count($collection->getSelect()->getPart('columns'))) {
            $this->_addUnprocessedEventsCount($collection);
        }
    }

    /**
     * Adds a subselect for each row to the result
     * SELECT main_table.*,
     *      (SELECT count(*) FROM ... where id = main_table.id) AS event_count
     * FROM `....` as main_table
     *
     * @param Mage_Index_Model_Resource_Process_Collection $collection
     */
    protected function _addUnprocessedEventsCount(Mage_Index_Model_Resource_Process_Collection $collection)
    {
        /** @var $eventsCollection Mage_Index_Model_Resource_Event_Collection */
        $eventsCollection = Mage::getResourceModel('index/event_collection');
        $eventsCollection->addProcessFilter('main_table.process_id', Mage_Index_Model_Process::EVENT_STATUS_NEW);
        $eventsCollectionSelect = (string)$eventsCollection->getSelectCountSql();
        $eventsCollectionSelect = str_replace('\'main_table.process_id\'', 'main_table.process_id', $eventsCollectionSelect);
        $collection->getSelect()->columns('(' . $eventsCollectionSelect . ') as `event_count`');
    }

    /**
     * Because of PHP Strict when function signatures won't match
     * @dispatch controller_front_init_before
     */
    public function rewriteCatalogResourceCategoryFlat()
    {
        $isMagento19 = version_compare(Mage::getVersion(), '1.9') > 0 ? '19' : '';
        $config      = Mage::getConfig();
        $config->setNode(
            'global/models/catalog_resource/rewrite/category_flat',
            'SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_Flat' . $isMagento19
        );
    }
}