<?php
/**
* @category  SchumacherFM
* @package   SchumacherFM_FastIndexer
* @copyright Copyright (c) http://www.schumacher.fm
* @license   private!
* @author    Cyrill at Schumacher dot fm @SchumacherFM
*/
/* @var $installer SchumacherFM_FastIndexer_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// set indexer model to manual and so speed up everything
$installer->run('
UPDATE `' . $installer->getTable('index/process') . '` SET `mode`=\'' . Mage_Index_Model_Process::MODE_MANUAL . '\'
');

$installer->endSetup();
