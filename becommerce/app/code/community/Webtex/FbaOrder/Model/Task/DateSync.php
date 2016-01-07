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
class Webtex_FbaOrder_Model_Task_DateSync
    extends Webtex_FbaOrder_Model_Task_Abstract
{
    protected $date;

    private $maxDate;

    function __construct($marketplace)
    {
        parent::__construct($marketplace);
        $this->date = $this->marketplace->getLastOrderSyncDate();
    }

    public function work()
    {
        try {
            /** @var FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersRequest $listRequest */
            $listRequest = $this->getCommonHelper()->getModel(
                'mwsOut/model_listAllFulfillmentOrdersRequest'
            );

            $listRequest->setQueryStartDateTime($this->date);
            $listRequest->setSellerId($this->marketplace->getMerchantId());
            /** @var FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersResponse $response */
            $response = $this->getClient()->listAllFulfillmentOrders($listRequest);
            if ($response->isSetListAllFulfillmentOrdersResult()) {
                /** @var FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersResult $result */
                $result = $response->getListAllFulfillmentOrdersResult();
                /** @var FBAOutboundServiceMWS_Model_FulfillmentOrder[] $fOrders */
                $fOrders = $result->getFulfillmentOrders()->getmember();
                foreach ($fOrders as $fOrder) {
                    $this->processOrder($fOrder);
                }
                if ($result->isSetNextToken()) {
                    /** @var Webtex_FbaOrder_Model_Task_TokenSync $tokenSyncTask */
                    $tokenSyncTask = Mage::getModel('wford/task_tokenSync', $this->marketplace);
                    $tokenSyncTask->initToken($result->getNextToken());
                    $tokenSyncTask->setPriority(1);
                    $this->getQueueHelper()->addJob($tokenSyncTask);
                }
            }
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function toString()
    {
        return "Get changed orders job by date: " . $this->date
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

    public function onSuccess()
    {
        $marketplaceDate = new DateTime(
            $this->marketplace->getLastOrderSyncDate(),
            new DateTimeZone('UTC')
        );
        if (isset($this->maxDate) && $this->maxDate > $marketplaceDate) {
            $this->marketplace->setLastOrderSyncDate($this->maxDate)->save();
        }
        return parent::onSuccess();
    }

    protected function processOrder(FBAOutboundServiceMWS_Model_FulfillmentOrder $order)
    {
        $lastDate = new DateTime(
            $order->getStatusUpdatedDateTime()
        );
        $lastDate->setTimezone(new DateTimeZone('UTC'));
        if (!isset($this->maxDate) || $this->maxDate < $lastDate) {
            $this->maxDate = $lastDate;
        }
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
        if (!$correspondingOrder
            || ($correspondingOrder->getOrder() instanceof FBAOutboundServiceMWS_Model_FulfillmentOrder
                && $correspondingOrder->getOrder()->getStatusUpdatedDateTime() != $order->getStatusUpdatedDateTime())
        ) {
            /** @var Webtex_FbaOrder_Model_Task_Sync $task */
            $task = Mage::getModel('wford/task_sync', $this->marketplace->getId());
            $task->initOrder($order->getSellerFulfillmentOrderId());
            $task->setPriority(1);
            $this->getQueueHelper()->addJob($task);
        }
    }
}
