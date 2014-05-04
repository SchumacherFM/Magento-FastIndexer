<?php
/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */

/**
 * Class SchumacherFM_FastIndexer_Test_Model_Db_Adapter_Pdo_MysqlTest
 * @doNotIndexAll
 * @loadSharedFixture global.yaml
 */
class SchumacherFM_FastIndexer_Test_Model_TableIndexMapperTest extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'SchumacherFM_FastIndexer_Model_TableIndexerMapper';

    /**
     * @return SchumacherFM_FastIndexer_Model_TableIndexerMapper
     */
    public function getInstance()
    {
        return new $this->class;
    }

    /**
     * @test
     */
    public function itShouldExist()
    {
        $this->assertTrue(class_exists($this->class), "Failed asserting {$this->class} exists");
    }

    /**
     * @test
     */
    public function itShouldNothingExtend()
    {
        $this->assertFalse(get_parent_class($this->getInstance()));
    }

    /**
     * @test
     */
    public function itShouldHaveMethods()
    {
        $methods  = array(
            '__construct',
            'getStores',
            'isIndexTable',
            'isFlatTable',
            'updateIndexMap',
            'setIndexMap',
            'getIndexMap',
            'updateFlatMap',
            'setFlatMap',
            'getFlatMap',
            'getTablesByIndexerCode',
        );
        $instance = $this->getInstance();
        $this->assertSame($methods, get_class_methods($instance));
    }

    /**
     * @test
     */
    public function itShouldDispatchAnEvent()
    {
        $instance = $this->getInstance();
        $events   = Mage::app()->getDispatchedEventCount('fastindexer_mapping_table');
        $this->assertSame(1, $events);
    }

    /**
     * @test
     */
    public function getStoresShouldReturnAnArrayWithStores()
    {
        $instance = $this->getInstance();
        $stores   = $instance->getStores();
        $this->assertTrue(is_array($stores), 'stores is an array');
        $this->assertGreaterThanOrEqual(1, count($stores));
        foreach ($stores as $store) {
            $this->assertInstanceOf('Mage_Core_Model_Store', $store);
        }
    }

    /**
     * @test
     */
    public function isIndexTableShouldReturnBoolean()
    {
        $instance = $this->getInstance();
        $this->assertTrue($instance->isIndexTable('catalog_product_attribute', 'catalog_product_index_eav_decimal_tmp'));
        $this->assertFalse($instance->isIndexTable('catalog_product_attribute', 'catalog_product_index_eav_decimal_XXX'));
    }

    /**
     * @test
     */
    public function isFlatTableShouldReturnBoolean()
    {
        $instance = $this->getInstance();
        $this->assertTrue($instance->isFlatTable('catalog_product_flat', Mage_Catalog_Model_Product_Flat_Indexer::ENTITY));
        $this->assertFalse($instance->isFlatTable('catalog_product_flat', 'catalog_product_index_eav_decimal_XXX'));
    }

    /**
     * @test
     */
    public function updateIndexMapShouldUpdateTheIndexMap()
    {
        $instance = $this->getInstance();
        $instance->updateIndexMap('test_indexer', 'test_table');
        $this->assertTrue($instance->isIndexTable('test_indexer', 'test_table'));
    }

    /**
     * @test
     */
    public function updateFlatMapShouldUpdateTheIndexMap()
    {
        $instance = $this->getInstance();
        $instance->updateFlatMap('test_indexer', 'test_table');
        $this->assertTrue($instance->isFlatTable('test_indexer', 'test_table'));
    }

    /**
     * @test
     */
    public function setGetIndexMapShouldReturnSame()
    {
        $instance = $this->getInstance();
        $testA    = array('test1' => array('test2' => 1));
        $instance->setIndexMap($testA);
        $this->assertSame($testA, $instance->getIndexMap());
    }

    /**
     * @test
     */
    public function setGetFlatMapShouldReturnSame()
    {
        $instance = $this->getInstance();
        $testA    = array('test1' => array('test2' => 1));
        $instance->setFlatMap($testA);
        $this->assertSame($testA, $instance->getFlatMap());
    }

    /**
     * @test
     */
    public function getTablesByIndexerCodeShouldReturnTablesArray()
    {
        $tables = $this->getInstance()->getTablesByIndexerCode('catalogsearch_fulltext');
        $this->assertSame(array(
            'catalogsearch_fulltext' => 1,
        ), $tables);
    }
}
