<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Helper_Product_Url_Rewrite extends Mage_Catalog_Helper_Product_Url_Rewrite
{

    /**
     * Prepare url rewrite left join statement for given select instance and store_id parameter.
     *
     * @param Varien_Db_Select $select
     * @param int              $storeId
     *
     * @return Mage_Catalog_Helper_Product_Url_Rewrite_Interface
     */
    public function joinTableToSelect(Varien_Db_Select $select, $storeId)
    {
        if (false === Mage::helper('schumacherfm_fastindexer')->disableJoinsInCatalogUrlHelper($storeId)) {
            parent::joinTableToSelect($select, $storeId);
        }
        echo 'Product: ' . __LINE__ . ': ' . $select;
        return $this;
    }
}
