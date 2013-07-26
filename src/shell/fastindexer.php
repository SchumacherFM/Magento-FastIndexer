<?php

require_once 'abstract.php';
require_once 'indexer.php';

define('FASTINDEXER_SHELL',true);

/**
 * @author      Cys
 */
class Mage_Shell_FastIndexer extends Mage_Shell_Compiler
{
    protected function _renameTables()
    {

        $tables = Mage::getSingleton('findex/fastIndexer');

        Zend_Debug::dump($tables->getTables());

    }

    /**
     * Run script
     *
     */
    public function run()
    {
        parent::run();

        if ($this->getArg('reindex') || $this->getArg('reindexall')) {
            $this->_renameTables();
        }

    }

}

$shell = new Mage_Shell_FastIndexer();
$shell->run();
