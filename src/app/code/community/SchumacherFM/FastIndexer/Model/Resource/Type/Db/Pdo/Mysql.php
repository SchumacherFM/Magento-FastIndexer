<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Type_Db_Pdo_Mysql extends Mage_Core_Model_Resource_Type_Db_Pdo_Mysql
{

    /**
     * Retrieve DB adapter class name
     *
     * @return string
     */
    protected function _getDbAdapterClassName()
    {
        return 'SchumacherFM_FastIndexer_Model_Db_Adapter_Pdo_Mysql';
    }
}
