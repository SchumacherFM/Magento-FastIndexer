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
class SchumacherFM_FastIndexer_Test_Model_Db_Adapter_Pdo_MysqlTest extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql';
    protected $_instance = null;

    /**
     * @return SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql
     */
    public function getInstance()
    {
        if (null === $this->_instance) {
            $config          = Mage::getConfig()->getNode('global/resources/default_setup/connection')->asArray();
            $this->_instance = new $this->class($config);
        }
        return $this->_instance;
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
    public function itShouldExtendVarienDbAdapterPdoMysql()
    {
        $this->assertInstanceOf('Varien_Db_Adapter_Pdo_Mysql', $this->getInstance());
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodEnableDbQuoteOptimization()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'enableDbQuoteOptimization'));
    }

    /**
     * @test
     */
    public function enableDbQuoteOptimizationShouldReturnBool()
    {
        $this->assertTrue(is_bool($this->getInstance()->enableDbQuoteOptimization()));
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodGetFastIndexerHelper()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'getFastIndexerHelper'));
    }

    /**
     * @test
     */
    public function getFastIndexerHelperShouldBeAnInstanceOfCoreHelper()
    {
        $this->assertInstanceOf('Mage_Core_Helper_Abstract', $this->getInstance()->getFastIndexerHelper());
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodGetIndexName()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'getIndexName'));
    }

    /**
     * @test
     */
    public function getIndexNameShouldReturnAnIndexNameWithOutTheShadowDbName()
    {
        $instance     = $this->getInstance();
        $shadowDbName = $instance->getFastIndexerHelper()->getShadowDbName(1);
        $name         = $instance->getIndexName($shadowDbName . '.my_test_table', array('field1', 'field2'));
        $this->assertSame('IDX_MY_TEST_TABLE_FIELD1_FIELD2', $name);
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodGetForeignKeyName()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'getForeignKeyName'));
    }

    /**
     * @test
     */
    public function getForeignKeyNameShouldReturnAnFkNameAsMd5()
    {
        $instance     = $this->getInstance();
        $shadowDbName = $instance->getFastIndexerHelper()->getShadowDbName(1);
        $name         = $instance->getForeignKeyName(
            $shadowDbName . '.primary_table_name', 'primary_field_name',
            $shadowDbName . '.reference_table_name', 'reference_field_name'
        );
        $this->assertSame('FK_F85B5968D00247181DDC56AE1690B768', $name);
    }

    /**
     * @test
     */
    public function getForeignKeyNameShouldReturnTheRemovedShadowDbName()
    {
        $instance     = $this->getInstance();
        $shadowDbName = $instance->getFastIndexerHelper()->getShadowDbName(1);
        $name         = $instance->getForeignKeyName(
            $shadowDbName . '.pt', 'pf',
            $shadowDbName . '.rt', 'rf'
        );
        $this->assertSame('FK_PT_PF_RT_RF', $name);
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodGetCreateTable()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'getCreateTable'));
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodGetForeignKeys()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'getForeignKeys'));
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodQuote()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'quote'));
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodQuery()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'query'));
    }

    /**
     * @test
     */
    public function itShouldHaveAMethodCastToNumeric()
    {
        $this->assertTrue(method_exists($this->getInstance(), 'castToNumeric'));
    }

    /**
     * @test
     */
    public function castToNumericShouldReturnFloat()
    {
        $expected = 4.0000002;
        $actual   = '' . $expected;
        $result   = $this->getInstance()->castToNumeric($actual);
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function castToNumericShouldReturnInt()
    {
        $expected = 4;
        $actual   = '' . $expected . '.0000';
        $result   = $this->getInstance()->castToNumeric($actual);
        $this->assertSame($expected, $result);
    }
}
