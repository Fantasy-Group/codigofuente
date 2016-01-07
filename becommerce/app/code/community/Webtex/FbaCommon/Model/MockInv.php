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
 * @package    Webtex_FbaCommon
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */

class Webtex_FbaCommon_Model_MockInv
    extends FBAInventoryServiceMWS_Client
{

    const PAGE_SIZE = 5;

    /**
     * @param FBAInventoryServiceMWS_Model_ListInventorySupplyRequest $request
     */
    public function listInventorySupply($request)
    {
        list($responseHeader, $responseMetadata, $inventorySupplyList, $nextToken) = $this->generateCommon($request);

        $result = new FBAInventoryServiceMWS_Model_ListInventorySupplyResult();
        $result->setInventorySupplyList($inventorySupplyList);
        if ($nextToken) {
            $result->setNextToken($nextToken);
        }

        $response = new FBAInventoryServiceMWS_Model_ListInventorySupplyResponse();
        $response->setListInventorySupplyResult($result);
        $response->setResponseMetadata($responseMetadata);
        $response->setResponseHeaderMetadata($responseHeader);

        return $response;
    }

    /**
     * @param FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenRequest $request
     */
    public function listInventorySupplyByNextToken($request)
    {
        list($responseHeader, $responseMetadata, $inventorySupplyList, $nextToken) = $this->generateCommon($request);
        $result = new FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenResult();
        $result->setInventorySupplyList($inventorySupplyList);
        if ($nextToken) {
            $result->setNextToken($nextToken);
        }

        $response = new FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenResponse();
        $response->setListInventorySupplyByNextTokenResult($result);
        $response->setResponseMetadata($responseMetadata);
        $response->setResponseHeaderMetadata($responseHeader);

        return $response;
    }

    private function generateCommon($request)
    {
        /** @var Webtex_FbaCommon_Model_Resource_Marketplace_Collection $mCollection */
        $mCollection = Mage::getModel('wfcom/marketplace')->getCollection();
        $marketplace = $mCollection->addFieldToFilter(
            'merchant_id',
            $request->getSellerId()
        )->getFirstItem();
        /** @var Webtex_FbaInventory_Model_Resource_Stock_Collection $skuList */
        $skuList = Mage::getModel('wfinv/stock')->getCollection();
        $skuList->setPageSize(self::PAGE_SIZE);
        if ($request instanceof FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenRequest
            && $request->isSetNextToken()
            && is_int($request->getNextToken())
        ) {
            $currentToken = $request->getNextToken();
        } else {
            $currentToken = 1;
        }
        if ($marketplace->getId()) {
            $skuList->addFieldToFilter(
                'marketplace_key',
                $marketplace->getId()
            );
        }

        /** @var Webtex_FbaInventory_Model_Stock[] $skuList */
        $skuList = $skuList->getItems();

        if (count($skuList) == self::PAGE_SIZE) {
            $nextToken = $currentToken + 1;
        } else {
            $nextToken = false;
        }

        $requestId = uniqid('reques_id_');
        $responseHeader = new FBAInventoryServiceMWS_Model_ResponseHeaderMetadata(
            $requestId,
            'fake-context',
            time()
        );

        $responseMetadata = new FBAInventoryServiceMWS_Model_ResponseMetadata(
            array('RequestId' => $requestId)
        );

        $inventorySupplyArray = array();

        foreach ($skuList as $skuItem) {
            $supply = new FBAInventoryServiceMWS_Model_InventorySupply();
            $supply->setSellerSKU($skuItem->getLinkSku());
            $supply->setFNSKU($skuItem->getLinkSku());
            $supply->setASIN($skuItem->getLinkSku());
            $supply->setCondition('NewItem');
            $supply->setTotalSupplyQuantity(rand(10, 500));
            $supply->setInStockSupplyQuantity(rand(10, 500));
            $inventorySupplyArray[$skuItem->getLinkSku()] = $supply;
        }


        $inventorySupplyList = new FBAInventoryServiceMWS_Model_InventorySupplyList();
        $inventorySupplyList->setmember(array_values($inventorySupplyArray));

        return array($responseHeader, $responseMetadata, $inventorySupplyList, $nextToken);
    }

}