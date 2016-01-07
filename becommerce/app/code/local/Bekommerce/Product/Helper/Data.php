<?php
class Bekommerce_Product_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getOnSaleUrl($data)
    {
        $url = Mage::getUrl('', array(
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => array(
                'brand_name' => $data,
                'p' => NULL
            )
        ));
 
        return $url;
    }
 
    public function getNotOnSaleUrl()
    {
        $url = Mage::getUrl('', array(
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => array(
                'brand_name' => NULL,
                'p' => NULL
            )
        ));
 
        return $url;
    }
}