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
/**
 * Class Webtex_FbaOrder_Model_Task_Create
 * @method FBAOutboundServiceMWS_Client getClient
 */
class Webtex_FbaOrder_Model_Task_Create
    extends Webtex_FbaOrder_Model_Task_Abstract
{
    /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest */
    protected $orderRequest;
    /** @var Webtex_FbaOrder_Model_Order */
    protected $order;

    public function initOrderRequest(Webtex_FbaOrder_Model_Order $order)
    {
        if (isset($this->order)) {
            throw new LogicException('Order is already initialized');
        }

        $this->order = $order;

        $this->orderRequest = $order->getRequest();
        if (!$this->orderRequest instanceof FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest) {
            throw new InvalidArgumentException('Order should contain CreateFulfillmentOrderRequest object');
        }
    }


    public function work()
    {
        if (!isset($this->orderRequest)) {
            throw new LogicException("You should initialize order request with initOrderRequest function");
        }

        try {
            $orderCreatingResult = $this->getClient()->createFulfillmentOrder($this->orderRequest);
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function toString()
    {
        return "Order creation request for for order: "
        . $this->orderRequest->getSellerFulfillmentOrderId()
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }


    public function onAdd()
    {
        $this->order->setInternalStatus(Webtex_FbaOrder_Model_Order::STATUS_IN_QUEUE)->save();
        foreach ($this->getItems() as $item) {
            $this->getMassHelper()->blockQty(
                $this->marketplace->getId(),
                $item->getSellerSKU(),
                $item->getQuantity()
            );
        }

        return parent::onAdd();
    }

    public function onFailure()
    {
        $messagesSummary = '';
        foreach ($this->getErrors() as $error) {
            $messagesSummary .= "\n" . $error;
        }
        $messagesSummary = ltrim($messagesSummary, "\n");
        $this->order->setErrorMessages($messagesSummary)
            ->setInternalStatus(Webtex_FbaOrder_Model_Order::STATUS_ERROR)
            ->save();
        foreach ($this->getItems() as $item) {
            $this->getMassHelper()->unblockQty(
                $this->marketplace->getId(),
                $item->getSellerSKU(),
                $item->getQuantity()
            );
        }

        return parent::onFailure();
    }

    public function onSuccess()
    {
        $this->order->setInternalStatus(Webtex_FbaOrder_Model_Order::STATUS_PLACED)
            ->save();
        foreach ($this->getItems() as $item) {
            $this->getMassHelper()->unblockAndSubQty(
                $this->marketplace->getId(),
                $item->getSellerSKU(),
                $item->getQuantity()
            );

        }

        /** @var Webtex_FbaOrder_Model_Task_Sync $task */
        $task = Mage::getModel('wford/task_sync', $this->marketplace->getId());
        $task->initOrder($this->order->getSellerFulfillmentOrderId());
        $this->getQueueHelper()->addJob($task);
        return parent::onSuccess();
    }

    /**
     * @return Webtex_FbaInventory_Model_ProductMass
     */
    protected function getMassHelper()
    {
        return Mage::getModel('wfinv/productMass');
    }

    /**
     * @return FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem[]
     */
    protected function getItems()
    {
        return $this->orderRequest->getItems()->getmember();
    }

}