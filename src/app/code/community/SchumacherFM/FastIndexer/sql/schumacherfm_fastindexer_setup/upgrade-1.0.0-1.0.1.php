<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */

/** @var $installer SchumacherFM_FastIndexer_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->checkExistingLockTable();

/** @var $ddlTable Varien_Db_Ddl_Table */
$ddlTable = $installer->getConnection()->newTable($installer->getLockTableName());

$ddlTable
    ->addColumn('indexer_code', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array(
        'nullable' => false,
        'primary'  => true,
    ), 'Indexer Code')
    ->addColumn('started_at', Varien_Db_Ddl_Table::TYPE_DECIMAL, '17,5', array(
        'nullable' => false,
    ), 'Start Date and Time')
    ->addColumn('ended_at', Varien_Db_Ddl_Table::TYPE_DECIMAL, '17,5', array(
        'nullable' => false,
    ), 'End Date and Time')
    ->addColumn('locked', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
        'nullable' => false,
        'unsigned' => true
    ), 'Is Locked')
    ->addIndex(
        $installer->getIdxName($installer->getLockTableName(), array('indexer_code')),
        array('indexer_code')
    )
    ->setComment('FastIndexer Lock');

$installer->getConnection()->createTable($ddlTable);
$installer->endSetup();