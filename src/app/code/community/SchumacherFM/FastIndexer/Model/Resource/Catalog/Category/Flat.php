<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Catalog_Category_Flat extends Mage_Catalog_Model_Resource_Category_Flat
{

    /**
     * @return SchumacherFM_FastIndexer_Helper_Data
     */
    protected function _getFiHelper()
    {
        return Mage::helper('schumacherfm_fastindexer');
    }

    /**
     * Load nodes by parent id
     *
     * @param Mage_Catalog_Model_Category|int $parentNode
     * @param integer                         $recursionLevel
     * @param integer                         $storeId
     *
     * @return Mage_Catalog_Model_Resource_Category_Flat
     */
    protected function _loadNodes($parentNode = null, $recursionLevel = 0, $storeId = 0)
    {
        if (false === $this->_getFiHelper()->optimizeUrlRewriteFlatCategory17()) {
            return parent::_loadNodes($parentNode, $recursionLevel, $storeId);
        }

        $_conn      = $this->_getReadAdapter();
        $startLevel = 1;
        $parentPath = '';
        if ($parentNode instanceof Mage_Catalog_Model_Category) {
            $parentPath = $parentNode->getPath();
            $startLevel = $parentNode->getLevel();
        } elseif (is_numeric($parentNode)) {
            $selectParent = $_conn->select()
                ->from($this->getMainStoreTable($storeId))
                ->where('entity_id = ?', $parentNode)
                ->where('store_id = ?', $storeId);
            $parentNode   = $_conn->fetchRow($selectParent);
            if ($parentNode) {
                $parentPath = $parentNode['path'];
                $startLevel = $parentNode['level'];
            }
        }
        $select = $_conn->select()
            ->from(
                array('main_table' => $this->getMainStoreTable($storeId)),
                array('entity_id',
                    new Zend_Db_Expr('main_table.' . $_conn->quoteIdentifier('name')),
                    new Zend_Db_Expr('main_table.' . $_conn->quoteIdentifier('path')),
                    'is_active',
                    'is_anchor',
                    'main_table.url_path as request_path'
                ))
            // fastindexer removed the joinLeft() to table core_url_rewrite because data in flat table available!
            ->where('main_table.is_active = ?', '1')
            ->where('main_table.include_in_menu = ?', '1')
            ->order('main_table.position');

        if ($parentPath) {
            $select->where($_conn->quoteInto("main_table.path like ?", "$parentPath/%"));
        }
        if ($recursionLevel != 0) {
            $levelField = $_conn->quoteIdentifier('level');
            $select->where($levelField . ' <= ?', $startLevel + $recursionLevel);
        }

        $inactiveCategories = $this->getInactiveCategoryIds();

        if (!empty($inactiveCategories)) {
            $select->where('main_table.entity_id NOT IN (?)', $inactiveCategories);
        }
        // SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $select);
        // Allow extensions to modify select (e.g. add custom category attributes to select)
        Mage::dispatchEvent('catalog_category_flat_loadnodes_before', array('select' => $select));

        $arrNodes = $_conn->fetchAll($select);
        $nodes    = array();
        foreach ($arrNodes as $node) {
            $node['id']         = $node['entity_id'];
            $nodes[$node['id']] = Mage::getModel('catalog/category')->setData($node);
        }

        return $nodes;
    }

    /**
     * Return parent categories of category
     *
     * @param Mage_Catalog_Model_Category $category
     * @param bool                        $isActive
     *
     * @return array
     */
    public function getParentCategories($category, $isActive = true)
    {
        if (false === $this->_getFiHelper()->optimizeUrlRewriteFlatCategory17()) {
            return parent::getParentCategories($category, $isActive);
        }

        $categories = array();
        $read       = $this->_getReadAdapter();
        $select     = $read->select()
            ->from(
                array('main_table' => $this->getMainStoreTable($category->getStoreId())),
                array('main_table.entity_id', 'main_table.name', 'main_table.url_path as request_path')
            )
            // fastindexer removed the joinLeft() to table core_url_rewrite because data in flat table available!
            ->where('main_table.entity_id IN (?)', array_reverse(explode(',', $category->getPathInStore())));
        if ($isActive) {
            $select->where('main_table.is_active = ?', '1');
        }
        $select->order('main_table.path ASC');
        //SchumacherFM_FastIndexer_Helper_Data::csdebug(__FILE__, __LINE__, $select);

        $result = $this->_getReadAdapter()->fetchAll($select);
        foreach ($result as $row) {
            $row['id']                     = $row['entity_id'];
            $categories[$row['entity_id']] = Mage::getModel('catalog/category')->setData($row);
        }
        return $categories;
    }
}
