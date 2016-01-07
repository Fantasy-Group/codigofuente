<?php
/*
 *  Created on May 3, 2011
 *  Author - Ecommerceoffice.com
 *  Copyright Ecommerceoffice.com © 2011. All Rights Reserved.
 *  Single Use, Limited Licence and Single Use No Resale Licence ["Single Use"]
 */
?>
<?php

class Ecommerceoffice_Homepage_Block_Page_Html_Footer extends Mage_Page_Block_Html_Footer
{

    public function getCopyright()
    {
        if (!$this->_copyright) {
            $this->_copyright = '<a href="http://www.magazento.com/">Magento Templates</a> '.Mage::getStoreConfig('design/footer/copyright');
        }

        return $this->_copyright;

    }
}
