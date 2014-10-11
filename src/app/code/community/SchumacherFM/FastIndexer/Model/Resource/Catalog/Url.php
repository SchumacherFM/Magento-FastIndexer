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

        if (
            false === $this->_getHelper()->excludeDisabledProducts($storeId) &&
            false === $this->_getHelper()->excludeNotVisibleProducts($storeId) &&
            false === $this->_getHelper()->excludeDisabledCategories($storeId)
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
     * Retrieve categories objects
     * Either $categoryIds or $path (with ending slash) must be specified
     *
     * @param int|array $categoryIds
     * @param int       $storeId
     * @param string    $path
     *
     * @return array
     */
    protected function _getCategories($categoryIds, $storeId = null, $path = null)
    {
        if (false === $this->_getHelper()->excludeDisabledCategories($storeId)) {
            return parent::_getCategories($categoryIds, $storeId, $path);
        }

        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $isActiveAttribute */
        $isActiveAttribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'is_active');
        $categories        = array();
        $adapter           = $this->_getReadAdapter();

        if (!is_array($categoryIds)) {
            $categoryIds = array($categoryIds);
        }

        // the method parent::_getCategories has a bug in getCheckSql
        $isActiveExpr = $adapter->getCheckSql('IFNULL(c.value_id,0) > 0', 'c.value', 'd.value');
        $select       = $adapter->select()
            ->from(array('main_table' => $this->getTable('catalog/category')), array(
                'main_table.entity_id',
                'main_table.parent_id',
                'main_table.level',
                'is_active' => $isActiveExpr,
                'main_table.path'
            ));

        // Prepare variables for checking whether categories belong to store
        if ($path === null) {
            $select->where('main_table.entity_id IN(?)', $categoryIds);
        } else {
            // Ensure that path ends with '/', otherwise we can get wrong results - e.g. $path = '1/2' will get '1/20'
            if (substr($path, -1) != '/') {
                $path .= '/';
            }

            $select
                ->where('main_table.path LIKE ?', $path . '%')
                ->order('main_table.path');
        }
        $table = $this->getTable(array('catalog/category', 'int'));
        $select->joinLeft(array('d' => $table),
            'd.attribute_id = :attribute_id AND d.store_id = 0 AND d.entity_id = main_table.entity_id',
            array()
        )
            ->joinLeft(array('c' => $table), // isActive table join for storeId > 0
                'c.attribute_id = :attribute_id AND c.store_id = :store_id AND c.entity_id = main_table.entity_id',
                array()
            );

        if (true === $this->_getHelper()->excludeDisabledCategories($storeId)) {
            $select->where($isActiveExpr . '=1');
        }

        if ($storeId !== null) {
            $rootCategoryPath       = $this->getStores($storeId)->getRootCategoryPath();
            $rootCategoryPathLength = strlen($rootCategoryPath);
        }
        $bind = array(
            'attribute_id' => (int)$isActiveAttribute->getId(),
            'store_id'     => (int)$storeId
        );

        $this->_addCategoryAttributeToSelect($select, 'name', $storeId);
        $this->_addCategoryAttributeToSelect($select, 'url_key', $storeId);
        $this->_addCategoryAttributeToSelect($select, 'url_path', $storeId);

        Mage::dispatchEvent('fastindexer_get_categories_select', array(
            'model'    => $this,
            'select'   => $select,
            'store_id' => $storeId
        ));

        $rowSet = $adapter->fetchAll($select, $bind);
        foreach ($rowSet as $row) {
            if ($storeId !== null) {
                // Check the category to be either store's root or its descendant
                // First - check that category's start is the same as root category
                if (substr($row['path'], 0, $rootCategoryPathLength) !== $rootCategoryPath) {
                    continue;
                }
                // Second - check non-root category - that it's really a descendant, not a simple string match
                if ((strlen($row['path']) > $rootCategoryPathLength)
                    && ($row['path'][$rootCategoryPathLength] !== '/')
                ) {
                    continue;
                }
            }

            $category = new Varien_Object($row);
            $category->setIdFieldName('entity_id');
            $category->setStoreId($storeId);
            $this->_prepareCategoryParentId($category);

            $categories[$category->getId()] = $category;
        }
        unset($rowSet);

        return $categories;
    }

    /**
     * FastIndexer
     *
     * @param Varien_Db_Select $select
     * @param string           $attributeCode
     * @param int              $storeId
     */
    private function _addCategoryAttributeToSelect(Varien_Db_Select $select, $attributeCode, $storeId)
    {
        $adapter = $this->_getReadAdapter();
        /** @var $adapter SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql */

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

        $tableAlias = 'cat' . $attributeCode;
        $tAttrId    = (int)$this->_categoryAttributes[$attributeCode]['attribute_id'];
        $tTypeId    = (int)$this->_categoryAttributes[$attributeCode]['entity_type_id'];

        if (1 === (int)$this->_categoryAttributes[$attributeCode]['is_global'] || $storeId == 0) {
            $joinColumns = array(
                $tableAlias . '.entity_type_id = ' . $tTypeId,
                $tableAlias . '.store_id = 0',
                $tableAlias . '.attribute_id = ' . $tAttrId,
                'main_table.entity_id = ' . $tableAlias . '.entity_id',
            );

            $select
                ->joinLeft(
                    array($tableAlias => $this->_categoryAttributes[$attributeCode]['table']),
                    implode(' AND ', $joinColumns),
                    array()
                )
                ->columns(array($attributeCode => $tableAlias . '.value'));
        } else {
            $t1        = 't1' . $tableAlias;
            $t2        = 't2' . $tableAlias;
            $valueExpr = $adapter->getCheckSql('IFNULL(' . $t2 . '.value_id,0) > 0', $t2 . '.value', $t1 . '.value');
            $select
                ->joinLeft(
                    array($t1 => $this->_categoryAttributes[$attributeCode]['table']),
                    'main_table.entity_id = ' . $t1 . '.entity_id AND ' . $t1 . '.store_id = 0 AND ' . $t1 . '.attribute_id = ' . $tAttrId,
                    array()
                )
                ->joinLeft(
                    array($t2 => $this->_categoryAttributes[$attributeCode]['table']),
                    $t1 . '.entity_id = ' . $t2 . '.entity_id AND ' . $t1 . '.attribute_id = ' . $t2 . '.attribute_id AND ' .
                    $t2 . '.store_id = ' . $storeId,
                    array()
                )
                ->columns(array($attributeCode => $valueExpr));
        }
    }

    /**
     * fastIndexer method and only used in _getProducts
     *
     * @param Varien_Db_Select $select
     * @param int              $storeId
     *
     * @return $this
     */
    private function _excludeDisabledCategories(Varien_Db_Select $select, $storeId)
    {
        if (false === $this->_getHelper()->excludeDisabledCategories($storeId)) {
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
