<?php

class Sharespine_Plugboard_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {

    protected $_canUseForMultishipping      = false;
    protected $_canUseCheckout              = false;

    public function canUseInternal() {
        return Mage::getSingleton('api/server')->getAdapter() != null;
    }

}
