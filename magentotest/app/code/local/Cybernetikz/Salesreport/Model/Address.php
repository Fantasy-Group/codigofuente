<?php
/**
*	Author		: 	Cybernetikz
*	Author Email:   info@cybernetikz.com
*	Blog		: 	http://blog.cybernetikz.com
*	Website		: 	http://www.cybernetikz.com
*/

class Cybernetikz_Salesreport_Model_Address {
    public function toOptionArray()
    {
        return array(
            array('value'=>'billing', 'label'=>Mage::helper('salesreport')->__('Billing Address')),
			array('value'=>'shipping', 'label'=>Mage::helper('salesreport')->__('Shipping Address'))                 
        );
    }
}