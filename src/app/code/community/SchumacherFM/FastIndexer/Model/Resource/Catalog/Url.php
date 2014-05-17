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

        if (false === $this->_getHelper()->isEnabled() ||
            (
                false === $this->_getHelper()->excludeDisabledProducts($storeId) &&
                false === $this->_getHelper()->excludeNotVisibleProducts($storeId) &&
                false === $this->_getHelper()->excludeDisableCategories($storeId)
            )
        ) {
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
        $this->_addProductStatusToSelect($select, $storeId);
        $this->_addProductVisibilityToSelect($select, $storeId);
        $this->_addProductAttributeToSelect($select, 'name', $storeId);
        $this->_addProductAttributeToSelect($select, 'url_key', $storeId);
        $this->_addProductAttributeToSelect($select, 'url_path', $storeId);
        /*</fastindexer>*/

        if (false === empty($productIds)) {
            $select->where('e.entity_id IN(?)', $productIds);
        }

        Mage::dispatchEvent('fastindexer_get_products_select', array(
            'model'    => $this,
            'select'   => $select,
            'store_id' => $storeId
        ));

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

        if (count($products) > 0 && false === $this->_getHelper()->excludeCategoryPathInProductUrl($storeId)) {
            $select = $adapter->select()
                ->from(
                    $this->getTable('catalog/category_product'),
                    array('product_id', 'category_id')
                )
                ->where('product_id IN(?)', array_keys($products));

            $this->_excludeDisabledCategories($select, $storeId); // fastindexer

            $categories = $adapter->fetchAll($select);
            foreach ($categories as $category) {
                $productId     = $category['product_id'];
                $categoryIds   = $products[$productId]->getCategoryIds();
                $categoryIds[] = $category['category_id'];
                $products[$productId]->setCategoryIds($categoryIds);
            }
        }

        return $products;
    }

    /**
     * FastIndexer
     * Keep method private because of the last two args which can be used for SQL injections
     *
     * @param Varien_Db_Select $select
     * @param string           $attributeCode
     * @param int              $storeId
     * @param string           $sqlCompare
     * @param string|int       $attributeValue
     * @param bool             $addAttributeToColumn
     */
    private function _addProductAttributeToSelect(
        Varien_Db_Select $select,
        $attributeCode,
        $storeId,
        $sqlCompare = null,
        $attributeValue = null,
        $addAttributeToColumn = true
    )
    {
        $adapter = $this->_getReadAdapter();
        /** @var $adapter SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql */

        if (!isset($this->_productAttributes[$attributeCode])) {
            $attribute                                = $this->getProductModel()->getResource()->getAttribute($attributeCode);
            $this->_productAttributes[$attributeCode] = array(
                'entity_type_id' => $attribute->getEntityTypeId(),
                'attribute_id'   => $attribute->getId(),
                'table'          => $attribute->getBackend()->getTable(),
                'is_global'      => $attribute->getIsGlobal()
            );
            unset($attribute);
        }

        $tableAlias = 'tj' . $attributeCode;
        $tAttrId    = (int)$this->_productAttributes[$attributeCode]['attribute_id'];
        $tTypeId    = (int)$this->_productAttributes[$attributeCode]['entity_type_id'];

        if (1 === (int)$this->_productAttributes[$attributeCode]['is_global'] || $storeId == 0) {
            $joinColumns = array(
                $tableAlias . '.entity_type_id = ' . $tTypeId,
                $tableAlias . '.store_id = 0',
                $tableAlias . '.attribute_id = ' . $tAttrId,
                'e.entity_id = ' . $tableAlias . '.entity_id',
            );
            if (null !== $sqlCompare && null !== $attributeValue) {
                $joinColumns[] = $tableAlias . '.value ' . $sqlCompare . ' ' . $attributeValue;
            }

            $select->join(
                array($tableAlias => $this->_productAttributes[$attributeCode]['table']),
                implode(' AND ', $joinColumns),
                array()
            );
            if (true === $addAttributeToColumn) {
                $select->columns(array($attributeCode => $tableAlias . '.value'));
            }
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
            if (true === $addAttributeToColumn) {
                $select->columns(array($attributeCode => $valueExpr));
            }

            if (null !== $sqlCompare && null !== $attributeValue) {
                $select->where($valueExpr . ' ' . $sqlCompare . ' ' . $attributeValue);
            }
        }
    }

    /**
     * FastIndexer
     *
     * @param Varien_Db_Select $select
     * @param     int          $storeId
     */
    private function _addProductStatusToSelect(Varien_Db_Select $select, $storeId)
    {
        if (true === $this->_getHelper()->excludeDisabledProducts($storeId)) {
            $this->_addProductAttributeToSelect($select, 'status', $storeId, '!=', Mage_Catalog_Model_Product_Status::STATUS_DISABLED, false);
        }
    }

    /**
     * FastIndexer
     *
     * @param Varien_Db_Select $select
     * @param  int             $storeId
     */
    private function _addProductVisibilityToSelect(Varien_Db_Select $select, $storeId)
    {
        if (true === $this->_getHelper()->excludeNotVisibleProducts($storeId)) {
            $this->_addProductAttributeToSelect($select, 'visibility', $storeId, '!=', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE, false);
        }
    }

    /**
     * fastIndexer method
     *
     * @param Varien_Db_Select $select
     * @param int              $storeId
     *
     * @return $this
     */
    protected function _excludeDisabledCategories(Varien_Db_Select $select, $storeId)
    {
        if (false === $this->_getHelper()->excludeDisableCategories($storeId)) {
            return $this;
        }
        $attributeCode = 'is_active';

        if (!isset($this->_categoryAttributes[$attributeCode])) {
            $attribute = $this->getCategoryModel()->getResource()->getAttribute($attributeCode);

            $this->_categoryAttributes[$attributeCode] = array(
                'entity_type_id' => $attribute->getEntityTypeId(),
                'attribute_id'   => $attribute->getId(),
                'table'          => $attribute->getBackend()->getTable(),
                'is_global'      => $attribute->getIsGlobal(),
                'is_static'      => $attribute->isStatic()
            );
            unset($attribute);
        }

        $tableAlias   = 'tjIsActive';
        $tAttrId      = (int)$this->_categoryAttributes[$attributeCode]['attribute_id'];
        $disableValue = 0;
        // <@todo remove duplicate code>
        $t1        = 't1' . $tableAlias;
        $t2        = 't2' . $tableAlias;
        $mainTable = $this->getTable('catalog/category_product');
        $valueExpr = $this->_getReadAdapter()->getCheckSql('IFNULL(' . $t2 . '.value_id,0) > 0', $t2 . '.value', $t1 . '.value');
        $select
            ->join(
                array($t1 => $this->_categoryAttributes[$attributeCode]['table']),
                $mainTable . '.category_id = ' . $t1 . '.entity_id AND ' . $t1 . '.store_id = 0 AND ' . $t1 . '.attribute_id = ' . $tAttrId,
                array()
            )
            ->joinLeft(
                array($t2 => $this->_categoryAttributes[$attributeCode]['table']),
                $t1 . '.entity_id = ' . $t2 . '.entity_id AND ' . $t1 . '.attribute_id = ' . $t2 . '.attribute_id AND ' .
                $t2 . '.store_id = ' . $storeId,
                array()
            );
        $select->where($valueExpr . ' != ' . $disableValue);
        // </@todo remove duplicate code>
        return $this;
    }

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }
}
