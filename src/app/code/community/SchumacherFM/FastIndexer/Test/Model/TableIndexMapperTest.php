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
     * @return SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
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
    public function itShouldHaveFourMethods()
    {
        $methods  = array(
            'isIndexTable',
            'updateMap',
            'setMap',
            'getMap',
            'getTablesByIndexerCode',
        );
        $instance = $this->getInstance();
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($instance, $method), 'Missing method: ' . $method);
        }
    }
}
