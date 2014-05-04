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
class SchumacherFM_FastIndexer_Test_Model_TableRollbackTest extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'SchumacherFM_FastIndexer_Model_TableRollback';

    /**
     * @return SchumacherFM_FastIndexer_Model_TableRollback
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
    public function itShouldExtendAbstractFastIndexer()
    {
        $this->assertInstanceOf('SchumacherFM_FastIndexer_Model_AbstractTable', $this->getInstance());
    }

    /**
     * @test
     */
    public function itShouldHaveMethods()
    {
        $methods = array(
            'rollbackIndexTables',
            '__construct',
            'getHelper',
            'setResource',
        );
        $this->assertSame($methods, get_class_methods($this->getInstance()));
    }
}
