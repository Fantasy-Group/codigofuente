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
class Webtex_FbaInventory_Model_Task_TokenSync extends Webtex_FbaInventory_Model_Task_Abstract
{
    protected $token;

    public function initToken($token)
    {
        if (isset($this->token)) {
            throw new LogicException('Token is already initialized');
        }

        $this->token = $token;

    }

    public function isUrgent()
    {
        return true;
    }

    public function work()
    {
        if (!isset($this->token)) {
            throw new LogicException("You should initialize token with initToken function");
        }

        try {
            /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenRequest $inventoryRequest */
            $inventoryRequest = $this->getCommonHelper()
                ->getModel('mwsInv/model_listInventorySupplyByNextTokenRequest');
            $inventoryRequest->setNextToken($this->token);
            $inventoryRequest->setSellerId($this->marketplace->getMerchantId());
            $inventoryResponse = $this->getClient()->listInventorySupplyByNextToken($inventoryRequest);
            if ($inventoryResponse->isSetListInventorySupplyByNextTokenResult()) {
                /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyByNextTokenResult $result */
                $result = $inventoryResponse->getListInventorySupplyByNextTokenResult();
                $this->processListInventorySupplyResult($result);
                $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
            }
        } catch (FBAInventoryServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function toString()
    {
        return "Inventory sync by next token."
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }
}
