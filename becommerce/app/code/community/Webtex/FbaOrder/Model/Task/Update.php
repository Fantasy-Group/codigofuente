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
class Webtex_FbaOrder_Model_Task_Update
    extends Webtex_FbaOrder_Model_Task_Abstract
{
    protected $orderId;

    public function initOrderId($orderId)
    {
        if (isset($this->orderId)) {
            throw new LogicException('Order ID is already initialized');
        }

        $this->orderId = $orderId;

    }

    public function work()
    {
        if (!isset($this->orderId)) {
            throw new LogicException("You should initialize order ID with initOrderID function");
        }

        try {
            /** @var FBAOutboundServiceMWS_Model_GetFulfillmentOrderRequest $orderDetailsRequest */
            $orderDetailsRequest = $this->getCommonHelper()->getModel(
                'mwsOut/model_getFulfillmentOrderRequest'
            );
            $orderDetailsRequest->setSellerFulfillmentOrderId($this->orderId);
            $orderDetailsRequest->setSellerId($this->marketplace->getMerchantId());
            $result = $this->getClient()->getFulfillmentOrder($orderDetailsRequest);
            if ($result->isSetGetFulfillmentOrderResult()) {
                $this->processOrder($result->getGetFulfillmentOrderResult());
            }
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function toString()
    {
        return "Update order job for order: " . $this->orderId
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

    protected function processOrder(FBAOutboundServiceMWS_Model_GetFulfillmentOrderResult $result)
    {
        // TODO: Implement order processing order
    }


}