<?php

/**
 * @category  SchumacherFM
 * @package   SchumacherFM_FastIndexer
 * @copyright Copyright (c) http://www.schumacher.fm
 * @license   see LICENSE.md file
 * @author    Cyrill at Schumacher dot fm @SchumacherFM
 */
class SchumacherFM_FastIndexer_Model_Index_Process extends Mage_Index_Model_Process
{
    /**
     * if you ever have a previous installed module which also rewrites Mage_Index_Model_Process
     * you can use this trait and a controller_front_init_before event to resolve perfectly the conflict.
     * talk to the author if you need help.
     */
    use SchumacherFM_FastIndexer_Model_Index_ProcessTrait;
}
