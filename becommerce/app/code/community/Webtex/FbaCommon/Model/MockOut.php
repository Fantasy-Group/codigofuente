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
class Webtex_FbaCommon_Model_MockOut
    extends FBAOutboundServiceMWS_Client
{
    const PAGE_SIZE = 5;

    /**
     * @param FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest $request
     */
    public function createFulfillmentOrder($request)
    {
        $requestId = uniqid('request_id_');
        if (!$request->getShippingSpeedCategory()) {
            throw new FBAOutboundServiceMWS_Exception(
                array(
                    'StatusCode' => 400,
                    'ErrorCode' => 400,
                    'ErrorType' => 'InvalidRequestException',
                    'Message' => 'InvalidRequestException',
                    'RequestId' => $requestId
                )
            );
        }
        $meta = new FBAOutboundServiceMWS_Model_ResponseMetadata();
        $meta->setRequestId($requestId);
        $header = new FBAOutboundServiceMWS_Model_ResponseHeaderMetadata(
            $requestId,
            'context',
            time()
        );
        $response = new FBAOutboundServiceMWS_Model_CreateFulfillmentOrderResponse();
        $response->setResponseHeaderMetadata($header);
        $response->setResponseMetadata($meta);

        return $response;
    }

    /**
     * @param FBAOutboundServiceMWS_Model_GetFulfillmentOrderRequest $request
     * @return FBAOutboundServiceMWS_Model_GetFulfillmentOrderResponse
     */
    public function getFulfillmentOrder($request)
    {
        $this->generateFulfillmentOrders($request);
        $requestId = uniqid('request_id_');
        $meta = new FBAOutboundServiceMWS_Model_ResponseMetadata();
        $meta->setRequestId($requestId);
        $header = new FBAOutboundServiceMWS_Model_ResponseHeaderMetadata(
            $requestId,
            'context',
            time()
        );
        $marketplace = $this->getMarketplace($request);
        /** @var Webtex_FbaOrder_Model_Order $fOrder */
        $fOrder = Mage::getModel('wford/order')->getCollection()
            ->addFieldToFilter(
                'seller_fulfillment_order_id',
                $request->getSellerFulfillmentOrderId()
            )->addFieldToFilter(
                'marketplace_key',
                $marketplace->getId()
            )->getFirstItem();
        if (!$fOrder || !$fOrder->getId()) {
            throw new FBAOutboundServiceMWS_Exception(
                array(
                    'StatusCode' => 400,
                    'ErrorCode' => 400,
                    'ErrorType' => 'InvalidParameterValue',
                    'Message' => 'InvalidParameterValue',
                    'RequestId' => $requestId
                )
            );
        }
        $fOrderItems = array();
        $fOrderShipments = array();
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem[] $fOrderRequestItems */
        $fOrderRequestItems = $fOrder->getRequest()->getItems()->getmember();
        foreach ($fOrderRequestItems as $rItem) {
            $oItem = new FBAOutboundServiceMWS_Model_FulfillmentOrderItem();
            $oItem->setSellerSKU($rItem->getSellerSKU());
            $oItem->setSellerFulfillmentOrderItemId($rItem->getSellerFulfillmentOrderItemId());
            $oItem->setQuantity($rItem->getQuantity());
            $fOrderItems[] = $oItem;
            $sItem = new FBAOutboundServiceMWS_Model_FulfillmentShipment();
            $sItem->setAmazonShipmentId(
                $fOrder->getSellerFulfillmentOrderId() .
                '_' .
                $oItem->getSellerFulfillmentOrderItemId()
            );
            $sItem->setFulfillmentCenterId('id');
            $sItem->setFulfillmentShipmentStatus('SHIPPED');
            $ssItem = new FBAOutboundServiceMWS_Model_FulfillmentShipmentItem();
            $ssItem->setSellerSKU($rItem->getSellerSKU());
            $ssItem->setQuantity($rItem->getQuantity());
            $ssItem->setPackageNumber(
                $fOrder->getSellerFulfillmentOrderId() .
                '_' .
                $oItem->getSellerFulfillmentOrderItemId()
            );
            $ssItem->setSellerFulfillmentOrderItemId($rItem->getSellerFulfillmentOrderItemId());
            $ssList = new FBAOutboundServiceMWS_Model_FulfillmentShipmentItemList();
            $ssList->setmember(array($ssItem));
            $sItem->setFulfillmentShipmentItem($ssList);
            $pack = new FBAOutboundServiceMWS_Model_FulfillmentShipmentPackage();
            $pack->setPackageNumber($ssItem->getPackageNumber());
            $pack->setCarrierCode('some_carrier');
            $pack->setTrackingNumber($pack->getPackageNumber());
            $packList = new FBAOutboundServiceMWS_Model_FulfillmentShipmentPackageList();
            $packList->setmember(array($pack));
            $sItem->setFulfillmentShipmentPackage($packList);
            $fOrderShipments[] = $sItem;
        }
        $fOrderItemList = new FBAOutboundServiceMWS_Model_FulfillmentOrderItemList();
        $fOrderItemList->setmember($fOrderItems);
        $fOrderShipmentList = new FBAOutboundServiceMWS_Model_FulfillmentShipmentList();
        $fOrderShipmentList->setmember($fOrderShipments);
        $result = new FBAOutboundServiceMWS_Model_GetFulfillmentOrderResult();
        $result->setFulfillmentOrder(clone $fOrder->getOrder());
        $result->setFulfillmentOrderItem($fOrderItemList);
        $result->setFulfillmentShipment($fOrderShipmentList);
        $response = new FBAOutboundServiceMWS_Model_GetFulfillmentOrderResponse();
        $response->setResponseHeaderMetadata($header);
        $response->setResponseMetadata($meta);
        $response->setGetFulfillmentOrderResult($result);

        return $response;
    }

    /**
     * @param FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsRequest $request
     *
     * @return FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsResponse
     */
    public function getPackageTrackingDetails($request)
    {
        $requestId = uniqid('request_id_');
        $packNumber = explode('_', $request->getPackageNumber());
        /** @var Webtex_FbaOrder_Model_Order $fulfillmentOrder */
        $fulfillmentOrder = Mage::getModel('wford/order')->load($packNumber[1]);
        if ($fulfillmentOrder
            && $fulfillmentOrder->getId()
            && $fulfillmentOrder->getShipment()
        ) {
            $meta = new FBAOutboundServiceMWS_Model_ResponseMetadata();
            $meta->setRequestId($requestId);
            $header = new FBAOutboundServiceMWS_Model_ResponseHeaderMetadata(
                $requestId,
                'context',
                time()
            );
            $result = new FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsResult();
            /** @var FBAOutboundServiceMWS_Model_FulfillmentShipment[] $shipments */
            $shipments = $fulfillmentOrder->getShipment()->getmember();
            foreach ($shipments as $shipment) {
                if ($shipment->isSetFulfillmentShipmentPackage()) {
                    /** @var FBAOutboundServiceMWS_Model_FulfillmentShipmentPackage[] $packages */
                    $packages = $shipment->getFulfillmentShipmentPackage()->getmember();
                    foreach ($packages as $package) {
                        if ($package->getPackageNumber() == $request->getPackageNumber()) {
                            $result->setTrackingNumber($package->getTrackingNumber());
                            $result->setPackageNumber($request->getPackageNumber());
                            $result->setCarrierCode($package->getCarrierCode());
                            $result->setCarrierPhoneNumber('123-123-1234');
                            $result->setCarrierURL('www.example.com');
                            break;
                        }

                    }
                }
            }
            if ($result->isSetPackageNumber()) {
                $response = new FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsResponse();
                $response->setResponseHeaderMetadata($header);
                $response->setResponseMetadata($meta);
                $response->setGetPackageTrackingDetailsResult($result);

                return $response;
            }
        }
        throw new FBAOutboundServiceMWS_Exception(
            array(
                'StatusCode' => 400,
                'ErrorCode' => 400,
                'ErrorType' => 'InvalidParameterValue',
                'RequestId' => $requestId
            )
        );
    }


    /**
     * @param FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersRequest $request
     * @see FBAOutboundServiceMWS_Model_ListAllFulfillmentOrders
     * @return FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersResponse
     *
     * @throws FBAOutboundServiceMWS_Exception
     */
    public function listAllFulfillmentOrders($request)
    {
        list($fulfillmentOrderList, $header, $meta, $nextToken) = $this->generateFulfillmentOrders($request);
        $result = new FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersResult();
        $result->setFulfillmentOrders($fulfillmentOrderList);
        if ($nextToken) {
            $result->setNextToken($nextToken);
        }
        $response = new FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersResponse();
        $response->setResponseHeaderMetadata($header);
        $response->setResponseMetadata($meta);
        $response->setListAllFulfillmentOrdersResult($result);

        return $response;
    }

    /**
     * @param FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenRequest $request
     * @return FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenResponse
     */
    public function listAllFulfillmentOrdersByNextToken($request)
    {
        list($fulfillmentOrderList, $header, $meta, $nextToken) = $this->generateFulfillmentOrders($request);
        $result = new FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenResult();
        $result->setFulfillmentOrders($fulfillmentOrderList);
        if ($nextToken) {
            $result->setNextToken($nextToken);
        }
        $response = new FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenResponse();
        $response->setResponseHeaderMetadata($header);
        $response->setResponseMetadata($meta);
        $response->setListAllFulfillmentOrdersByNextTokenResult($result);

        return $response;
    }

    /**
     * @param FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersRequest | FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenRequest $request
     * @return array
     */
    private function generateFulfillmentOrders($request)
    {
        $requestId = uniqid('request_id_');
        $meta = new FBAOutboundServiceMWS_Model_ResponseMetadata();
        $meta->setRequestId($requestId);
        $header = new FBAOutboundServiceMWS_Model_ResponseHeaderMetadata(
            $requestId,
            'context',
            time()
        );
        $orders = array();
        $marketplace = $this->getMarketplace($request);
        /** @var Webtex_FbaOrder_Model_Resource_Order_Collection $ordersToSync */
        $ordersToSync = Mage::getModel('wford/order')->getCollection()
            ->addFieldToFilter(
                'request',
                array('notnull' => true)
            )->addFieldToFilter(
                '`order`',
                array('null' => true)
            )->addFieldToFilter(
                'marketplace_key',
                $marketplace->getId()
            );
        if ($request instanceof FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenRequest
            && $request->isSetNextToken()
            && is_int($request->getNextToken())
        ) {
            $currentToken = $request->getNextToken();
        } else {
            $currentToken = 1;
        }
        $ordersToSync = $ordersToSync->getItems();
        /** @var Webtex_FbaOrder_Model_Order[] $ordersToSync */
        if (count($ordersToSync) == self::PAGE_SIZE) {
            $nextToken = $currentToken + 1;
        } else {
            $nextToken = false;
        }
        foreach ($ordersToSync as $ord) {
            $fOrder = new FBAOutboundServiceMWS_Model_FulfillmentOrder();
            $fOrder->setSellerFulfillmentOrderId($ord->getSellerFulfillmentOrderId());
            $fOrder->setDisplayableOrderId($ord->getRequest()->getDisplayableOrderId());
            $fOrder->setDisplayableOrderDateTime($ord->getRequest()->getDisplayableOrderDateTime());
            $fOrder->setDisplayableOrderComment($ord->getRequest()->getDisplayableOrderComment());
            $fOrder->setShippingSpeedCategory($ord->getRequest()->getShippingSpeedCategory());
            $fOrder->setDestinationAddress($ord->getRequest()->getDestinationAddress());
            $fOrder->setFulfillmentPolicy($ord->getRequest()->getFulfillmentPolicy());
            $fOrder->setStatusUpdatedDateTime($ord->getRequest()->getDisplayableOrderDateTime());
            $fOrder->setFulfillmentOrderStatus('COMPLETE');
            $fOrder->setNotificationEmailList($ord->getRequest()->getNotificationEmailList());
            $ord->setOrder($fOrder)->save();

        }

        /** @var Webtex_FbaOrder_Model_Resource_Order_Collection $ordersToSync */
        $ordersToSync = Mage::getModel('wford/order')->getCollection()
            ->addFieldToFilter(
                'request',
                array('notnull' => true)
            )->addFieldToFilter(
                'marketplace_key',
                $marketplace->getId()
            );
        $ordersToSync->setPageSize(self::PAGE_SIZE);
        foreach($ordersToSync as $ord) {
            $orders[] = $ord->getOrder();
        }
        $fulfillmentOrderList = new FBAOutboundServiceMWS_Model_FulfillmentOrderList();
        $fulfillmentOrderList->setmember($orders);

        return array($fulfillmentOrderList, $header, $meta, $nextToken);
    }

    /**
     * @param $request
     * @return Webtex_FbaCommon_Model_Marketplace
     */
    private function getMarketplace($request)
    {
        /** @var Webtex_FbaCommon_Model_Resource_Marketplace_Collection $mCollection */
        $mCollection = Mage::getModel('wfcom/marketplace')->getCollection();

        return $mCollection->addFieldToFilter(
            'merchant_id',
            $request->getSellerId()
        )->getFirstItem();
    }

}