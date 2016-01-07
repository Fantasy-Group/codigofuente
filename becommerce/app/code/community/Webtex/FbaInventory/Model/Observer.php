<?php

class Webtex_FbaInventory_Model_Observer
{
    public function queueFinish()
    {
        $this->getInvHelper()->reindexProductsIfNeeded();
    }

    /**
     * @return Webtex_FbaInventory_Helper_Data
     */
    protected function getInvHelper()
    {
        return Mage::helper('wfinv');
    }

}