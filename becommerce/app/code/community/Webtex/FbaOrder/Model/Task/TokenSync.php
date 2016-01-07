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
class Webtex_FbaOrder_Model_Task_TokenSync
    extends Webtex_FbaOrder_Model_Task_DateSync
{
    protected $token;

    public function initToken($token)
    {
        if (isset($this->token)) {
            throw new LogicException('Token is already initialized');
        }

        $this->token = $token;

    }

    public function work()
    {
        if (!isset($this->token)) {
            throw new LogicException("You should initialize token with initToken function");
        }

        try {
            /** @var FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenRequest $listRequest */
            $listRequest = $this->getCommonHelper()->getModel(
                'mwsOut/model_listAllFulfillmentOrdersByNextTokenRequest'
            );

            $listRequest->setNextToken($this->token);
            $listRequest->setSellerId($this->marketplace->getMerchantId());
            /** @var FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenResponse $result */
            $result = $this->getClient()->listAllFulfillmentOrdersByNextToken($listRequest);
            if ($result->isSetListAllFulfillmentOrdersByNextTokenResult()) {
                $this->processList($result->getListAllFulfillmentOrdersByNextTokenResult());
            }
            $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
        } catch (FBAOutboundServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function toString()
    {
        return "Get changed orders job by token: " . $this->token
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

    protected function processList(FBAOutboundServiceMWS_Model_ListAllFulfillmentOrdersByNextTokenResult $result)
    {
        /** @var FBAOutboundServiceMWS_Model_FulfillmentOrder[] $list */
        $list = $result->getFulfillmentOrders()->getmember();
        foreach ($list as $order) {
            $this->processOrder($order);
        }
        if ($result->isSetNextToken()) {
            /** @var Webtex_FbaOrder_Model_Task_TokenSync $tokenSyncTask */
            $tokenSyncTask = Mage::getModel('wford/task_tokenSync', $this->marketplace);
            $tokenSyncTask->initToken($result->getNextToken());
            $tokenSyncTask->setPriority(1);
            $this->getQueueHelper()->addJob($tokenSyncTask);
        }
    }
}
