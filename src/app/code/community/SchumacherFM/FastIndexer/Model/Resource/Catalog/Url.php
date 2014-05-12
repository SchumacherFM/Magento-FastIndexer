<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Catalog_Url extends Mage_Catalog_Model_Resource_Url
{

    protected $_isFastIndexerFullReindex = false;

    protected function _construct()
    {
        parent::_construct();
        $this->_isFastIndexerFullReindex = Mage::getSingleton('schumacherfm_fastindexer/tableCreator')->isInitIndexTables();
    }

    /**
     * Skip this step when we're running a full reindex as tables are empty
     *
     * @param int $storeId
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Url
     */
    public function clearStoreInvalidRewrites($storeId)
    {
        if (true === $this->_isFastIndexerFullReindex) {
            return $this;
        }
        parent::clearStoreInvalidRewrites($storeId);
        return $this;
    }

    /**
     * Used in custom Model_Url
     *
     * @param $productIds
     * @param $storeId
     * @param $lastEntityId
     *
     * @return array
     */
//    public function getProductsByIds($productIds, $storeId, &$lastEntityId)
//    {
//        return $this->_getProducts($productIds, $storeId, $lastEntityId, $lastEntityId);
//    }

    /**
     * 8100 is disabled     id 17
     * W810i is not visible id 18
     *
     * Retrieve Product data objects
     * LOE: remove if status(=2) is disabled or visibility(=1) false
     *
     * @param int|array $productIds
     * @param int       $storeId
     * @param int       $entityId
     * @param int       $lastEntityId ref
     *
     * @return array
     */
    protected function _getProducts($productIds, $storeId, $entityId, &$lastEntityId)
    {
        $storeId = (int)$storeId;

        if (false === $this->_getHelper()->excludeDisabledProducts($storeId) && false === $this->_getHelper()->excludeNotVisibleProducts($storeId)) {
            return parent::_getProducts($productIds, $storeId, $entityId, $lastEntityId);
        }

        $products  = array();
        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        /** @var SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql $adapter */
        $adapter = $this->_getReadAdapter();
        if ($productIds !== null) {
            if (!is_array($productIds)) {
                $productIds = array($productIds);
            }
        }
        $bind = array(
            'website_id' => (int)$websiteId,
            'entity_id'  => (int)$entityId,
        );
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()
            //->useStraightJoin(true)
            ->from(array('e' => $this->getTable('catalog/product')), array('entity_id'))
            ->join(
                array('w' => $this->getTable('catalog/product_website')),
                'e.entity_id = w.product_id AND w.website_id = :website_id',
                array()
            )
            ->where('e.entity_id > :entity_id')
            ->order('e.entity_id')
            ->limit($this->_productLimit);

        // <fastindexer>
        $_attributes = array();
        if (true === $this->_getHelper()->excludeDisabledProducts($storeId)) {
            $_attributes['status'] = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
        }
        if (true === $this->_getHelper()->excludeNotVisibleProducts($storeId)) {
            $_attributes['visibility'] = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
        }

        foreach ($_attributes as $attributeCode => $disableValue) {
            $this->_getProductAttribute($attributeCode, -1, $storeId); // init attributes we do not need a product id

            $tableAlias = 'tj' . $attributeCode;
            $tAttrId    = (int)$this->_productAttributes[$attributeCode]['attribute_id'];
            $tTypeId    = (int)$this->_productAttributes[$attributeCode]['entity_type_id'];

            if ($this->_productAttributes[$attributeCode]['is_global'] || $storeId == 0) {
                $joinColumns = array(
                    $tableAlias . '.entity_type_id = ' . $tTypeId,
                    $tableAlias . '.store_id = 0',
                    $tableAlias . '.attribute_id = ' . $tAttrId,
                    'e.entity_id = ' . $tableAlias . '.entity_id',
                    $tableAlias . '.value != ' . $disableValue,
                );
                $select->join(
                    array($tableAlias => $this->_productAttributes[$attributeCode]['table']),
                    implode(' AND ', $joinColumns),
                    array()
                );
            } else {
                $t1        = 't1' . $tableAlias;
                $t2        = 't2' . $tableAlias;
                $valueExpr = $adapter->getCheckSql('IFNULL(' . $t2 . '.value_id,0) > 0', $t2 . '.value', $t1 . '.value');
                $select
                    ->join(
                        array($t1 => $this->_productAttributes[$attributeCode]['table']),
                        'e.entity_id = ' . $t1 . '.entity_id AND ' . $t1 . '.store_id = 0 AND ' . $t1 . '.attribute_id = ' . $tAttrId,
                        array()
                    )
                    ->joinLeft(
                        array($t2 => $this->_productAttributes[$attributeCode]['table']),
                        $t1 . '.entity_id = ' . $t2 . '.entity_id AND ' . $t1 . '.attribute_id = ' . $t2 . '.attribute_id AND ' .
                        $t2 . '.store_id = ' . $storeId,
                        array()
                    );
                $select->where($valueExpr . ' != ' . $disableValue);
            }
        }
        /*</fastindexer>*/

        if (false === empty($productIds)) {
            $select->where('e.entity_id IN(?)', $productIds);
        }

        $rowSet = $adapter->fetchAll($select, $bind);

        foreach ($rowSet as $row) {
            $product = new Varien_Object($row);
            $product->setIdFieldName('entity_id');
            $product->setCategoryIds(array());
            $product->setStoreId($storeId);
            $products[$product->getId()] = $product;
            $lastEntityId                = $product->getId();
        }
        unset($rowSet);

        if ($products) {

            if (false === $this->_getHelper()->excludeCategoryPathInProductUrl($storeId)) {
                $select     = $adapter->select()
                    ->from(
                        $this->getTable('catalog/category_product'),
                        array('product_id', 'category_id')
                    )
                    ->where('product_id IN(?)', array_keys($products));
                $categories = $adapter->fetchAll($select);
                foreach ($categories as $category) {
                    $productId     = $category['product_id'];
                    $categoryIds   = $products[$productId]->getCategoryIds();
                    $categoryIds[] = $category['category_id'];
                    $products[$productId]->setCategoryIds($categoryIds);
                }
            }

            foreach (array('name', 'url_key', 'url_path') as $attributeCode) {
                $attributes = $this->_getProductAttribute($attributeCode, array_keys($products), $storeId);
                foreach ($attributes as $productId => $attributeValue) {
                    $products[$productId]->setData($attributeCode, $attributeValue);
                }
            }
        }

        return $products;
    }

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }
}
