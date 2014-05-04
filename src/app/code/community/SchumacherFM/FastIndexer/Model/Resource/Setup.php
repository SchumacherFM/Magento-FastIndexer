<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
    /**
     * @return string
     */
    public function getLockTableName()
    {
        return $this->getTable('schumacherfm_fastindexer/lock');
    }

    /**
     * @return $this
     */
    public function checkExistingLockTable()
    {
        if ($this->tableExists($this->getLockTableName())) {
            $this->getConnection()->dropTable($this->getLockTableName());
        }
        return $this;
    }
}
