<?php

class Package_Module_Model_Resource_ModelName extends Mage_Core_Model_Resource_Db_Abstract {
    protected function _construct() {
        $this->_init('shortname/model_lowercase_underscore', 'model_lowercase_underscore_id');
    }
}