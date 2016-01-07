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
 * @package    Webtex_FbaOrder
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_FbaOrder_Model_Task_Sync
    extends Webtex_FbaOrder_Model_Task_Abstract
{
    protected $orderId;

    public function initOrder($orderId)
    {
        if (isset($this->orderId)) {
            throw new LogicException("Order ID is already initialized");
        }

        $this->orderId = $orderId;
    }

    public function work()
    {
        if (!isset($this->orderId)) {
            throw new LogicException("You should initialize order ID with initOrder method");
        }

        try {
            /** @var FBAOutboundServiceMWS_Model_GetFulfillmentOrderRequest $getOrderRequest */
            $getOrderRequest = $this->getCommonHelper()->getModel(
                'mwsOut/model_getFulfillmentOrderRequest'
            );
            $getOrderRequest->setSellerFulfillmentOrderId($this->orderId);
            $getOrderRequest->setSellerId($this->marketplace->getMerchantId());
            $response = $this->getClient()->getFulfillmentOrder($getOrderRequest);
            if ($response->isSetGetFulfillmentOrderResult()) {
                $this->syncOrder($response->getGetFulfillmentOrderResult());
            }
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;

    }

    private function syncOrder(FBAOutboundServiceMWS_Model_GetFulfillmentOrderResult $result)
    {
        /** @var FBAOutboundServiceMWS_Model_FulfillmentOrder $order */
        $order = $result->getFulfillmentOrder();
        /** @var FBAOutboundServiceMWS_Model_FulfillmentOrderItem $item */
        $item = $result->getFulfillmentOrderItem();
        /** @var FBAOutboundServiceMWS_Model_FulfillmentShipment $shipment */
        $shipment = $result->getFulfillmentShipment();
        /** @todo order result processing */

        /** @var Webtex_FbaOrder_Model_Resource_Order_Collection $ordCollection */
        $ordCollection = Mage::getModel('wford/order')->getCollection();

        /** @var Webtex_FbaOrder_Model_Order $correspondingOrder */
        $correspondingOrder = $ordCollection
            ->addFieldToFilter(
                'seller_fulfillment_order_id',
                $order->getSellerFulfillmentOrderId()
            )
            ->addFieldToFilter(
                'marketplace_key',
                $this->marketplace->getId()
            )->getFirstItem();

        if (!$correspondingOrder) {
            $correspondingOrder = Mage::getModel('wford/order');
            $correspondingOrder
                ->setSellerFulfillmentOrderId($order->getSellerFulfillmentOrderId())
                ->setMarketplaceKey($this->marketplace->getId());
        }

        $correspondingOrder
            ->setOrder($order)
            ->setItem($item)
            ->setShipment($shipment)
            ->save();

        $this->adjustShipment($correspondingOrder);

    }

    public function adjustShipment(Webtex_FbaOrder_Model_Order $order)
    {
        $shipmentList = $order->getShipment();
        if ($order->getMageOrderKey()
            && $shipmentList->isSetmember()
        ) {
            /** @var Mage_Sales_Model_Order $mageOrder */
            $mageOrder = Mage::getModel('sales/order')->load($order->getMageOrderKey());
            /** @var Webtex_FbaOrder_Model_OrderParser $parser */
            $parser = Mage::getModel('wford/orderParser', $mageOrder);
            if ($mageOrder
                && $mageOrder->getId()
            ) {
                $shipmentMap = $order->getShipmentMap();
                if (!$shipmentMap) {
                    $shipmentMap = array();
                }
                foreach ($shipmentList->getmember() as $amazonShipment) {
                    /** @var FBAOutboundServiceMWS_Model_FulfillmentShipment $amazonShipment */
                    if (!isset($shipmentMap[$amazonShipment->getAmazonShipmentId()])) {

                        $shipment = $parser->generateShipment(
                            $amazonShipment,
                            $this->marketplace->getId()
                        );

                        if ($shipment
                            && $shipment->getId()
                            && $amazonShipment->isSetFulfillmentShipmentPackage()
                        ) {
                            /** @var FBAOutboundServiceMWS_Model_FulfillmentShipmentPackage[] $aPackageList */
                            $aPackageList = $amazonShipment->getFulfillmentShipmentPackage()->getmember();
                            foreach ($aPackageList as $aPackage) {
                                if ($aPackage->isSetCarrierCode()
                                    && $aPackage->isSetTrackingNumber()
                                    && $aPackage->isSetPackageNumber()
                                ) {
                                    /** @var Webtex_FbaOrder_Model_Task_Track $trackTask */
                                    $trackTask = Mage::getModel('wford/task_track', $this->marketplace);
                                    $trackTask->initShipmentId($shipment->getId());
                                    $trackTask->initPackageNumber($aPackage->getPackageNumber());
                                    $trackTask->initFulfillmentOrder($order->getId());
                                    $trackTask->setPriority(1);
                                    $this->getQueueHelper()->addJob($trackTask);
                                }
                            }
                        }

                        if ($shipment === false) {
                            $shipmentMap[$amazonShipment->getAmazonShipmentId()] = false;
                        } else {
                            $shipmentMap[$amazonShipment->getAmazonShipmentId()] = $shipment->getId();
                        }
                    }
                }
                $order->setShipmentMap($shipmentMap)->save();
            }
        }
    }

    public function toString()
    {
        return "Sync order with number" . $this->orderId
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

}