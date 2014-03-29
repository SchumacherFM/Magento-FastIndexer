<?php

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
