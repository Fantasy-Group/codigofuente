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
class Webtex_FbaOrder_Model_Task_Track
    extends Webtex_FbaOrder_Model_Task_Abstract
{
    protected $mageShipmentId;

    protected $packageNumber;

    protected $fulfillmentOrder;

    public function initShipmentId($shipmentId)
    {
        if (isset($this->mageShipmentId)) {
            throw new LogicException("Shipment ID is already initialized");
        }

        $this->mageShipmentId = $shipmentId;
    }

    public function initPackageNumber($number)
    {
        if (isset($this->packageNumber)) {
            throw new LogicException("Package Number is already initialized");
        }

        $this->packageNumber = $number;
    }

    public function initFulfillmentOrder($orderId)
    {
        if (isset($this->fulfillmentOrder)) {
            throw new LogicException("Fulfillment Order is already initialized");
        }

        $this->fulfillmentOrder = $orderId;
    }

    public function work()
    {
        if (!isset($this->mageShipmentId)) {
            throw new LogicException("You should initialize shipment ID with initShipmentId method");
        }

        if (!isset($this->packageNumber)) {
            throw new LogicException("You should initialize package number with initPackageNumber method");
        }

        if (!isset($this->fulfillmentOrder)) {
            throw new LogicException("You should initialize Fulfillment Order with initFulfillmentOrder method");
        }

        try {
            /** @var FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsRequest $packRequest */
            $packRequest = $this->getCommonHelper()->getModel(
                'mwsOut/model_getPackageTrackingDetailsRequest'
            );
            $packRequest->setPackageNumber($this->packageNumber);
            $packRequest->setSellerId($this->marketplace->getMerchantId());
            $response = $this->getClient()->getPackageTrackingDetails($packRequest);
            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            $shipment = Mage::getModel('sales/order_shipment')->load($this->mageShipmentId);
            /** @var Webtex_FbaOrder_Model_Order $order */
            $order = Mage::getModel('wford/order')->load($this->fulfillmentOrder);
            if ($shipment
                && $shipment->getId()
                && $order
                && $order->getId()
                && $response->isSetGetPackageTrackingDetailsResult()
            ) {
                $currentTracks = $order->getTracks();
                /** @var FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsResult $result */
                $result = $response->getGetPackageTrackingDetailsResult();
                if (!isset($currentTracks[$result->getTrackingNumber()])) {
                    $currentTracks[$result->getTrackingNumber()] = $result;
                    $internalTrack = $order->getId() . "_" . $result->getTrackingNumber();
                    /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                    $track = Mage::getModel('sales/order_shipment_track');
                    $track->setCarrierCode('webtexPriority');
                    $track->setTitle('Package # ' . $result->getPackageNumber());
                    $track->setNumber($internalTrack);
                    $shipment->addTrack($track);
                    $shipment->getOrder()->setIsInProcess(true);
                    /** @var Mage_Core_Model_Resource_Transaction $transactionSave */
                    $transactionSave = Mage::getModel('core/resource_transaction');
                    $transactionSave
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();
                } elseif ($currentTracks[$result->getTrackingNumber()] != $result) {
                    $currentTracks[$result->getTrackingNumber()] = $result;
                }

                $order->setTracks($currentTracks);
                $order->save();
            }
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;

    }

    public function toString()
    {
        return "Sync tracking info for package #" . $this->packageNumber
        . " For shipping with ID:" . $this->mageShipmentId
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

}