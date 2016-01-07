<?php

/**
 * Webtex
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.webtexsoftware.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@webtexsoftware.com and we will send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to http://www.webtexsoftware.com for more information,
 * or contact us through this email: info@webtexsoftware.com.
 *
 * @category   Webtex
 * @package    Webtex_FbaInventory
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
abstract class Webtex_FbaInventory_Model_Task_Abstract extends Webtex_FbaCommon_Model_Task
{
    protected $url = '/FulfillmentInventory/2010-10-01';

    protected $clientType = 'mwsInv/client';

    protected $productsToRefreshStock = array();

    /**
     * @param FBAInventoryServiceMWS_Model_ListInventorySupplyResult | FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenResult $result
     * @return bool
     */
    protected function processListInventorySupplyResult($result)
    {
        if ($result->isSetNextToken()) {
            /** @var Webtex_FbaInventory_Model_Task_TokenSync $tokenSyncTask */
            $tokenSyncTask = Mage::getModel('wfinv/task_tokenSync', $this->marketplace);
            $tokenSyncTask->initToken($result->getNextToken());
            $tokenSyncTask->setPriority(1);
            $this->getQueueHelper()->addJob($tokenSyncTask);
        }
        /** @var Webtex_FbaInventory_Model_ProductMass $massProduct */
        $massProduct = Mage::getModel('wfinv/productMass');
        /** @var FBAInventoryServiceMWS_Model_InventorySupplyList $supplyList */
        $supplyList = $result->getInventorySupplyList();
        foreach ($supplyList->getmember() as $item) {
            /** @var FBAInventoryServiceMWS_Model_InventorySupply $item */
            $this->productsToRefreshStock = array_merge(
                $this->productsToRefreshStock,
                $massProduct->updateLevels(
                    $this->marketplace->getId(),
                    $item->getSellerSku(),
                    array(
                        Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY => $item->getInStockSupplyQuantity(),
                        Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY => $item->getTotalSupplyQuantity()
                    )
                )
            );
        }

        return true;
    }

    public function onSuccess()
    {
        if (count($this->productsToRefreshStock)) {
            /** @var Webtex_FbaInventory_Model_ProductMass $massProduct */
            $massProduct = Mage::getModel('wfinv/productMass');
            $changedProductIds = array();
            foreach ($massProduct->getCollection($this->productsToRefreshStock) as $product) {
                if ($product->checkLocalStock(false)) {
                    $changedProductIds[] = (int)$product->getMageProductKey();
                }
            }
            $this->getInvHelper()->addProductsToReindex($changedProductIds);
        }

        parent::onSuccess();
    }


    /**
     * @return Webtex_FbaInventory_Helper_Data
     */
    protected function getInvHelper()
    {
        return Mage::helper('wfinv');

    }

}