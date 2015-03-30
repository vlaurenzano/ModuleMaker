<?php

class Package_Module_Model_Resource_ModelName_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    protected function _construct() {
        $this->_init('shortname/model_lowercase_underscore');
    }

}
