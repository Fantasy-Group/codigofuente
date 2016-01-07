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
class Webtex_FbaInventory_Model_Task_DateSync extends Webtex_FbaInventory_Model_Task_Abstract
{
    protected $date;

    function __construct($marketplace)
    {
        parent::__construct($marketplace);
        $this->date = $this->marketplace->getLastInventorySyncDate();
    }


    public function work()
    {
        try {
            /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyRequest $inventoryRequest */
            $inventoryRequest = $this->getCommonHelper()->getModel('mwsInv/model_listInventorySupplyRequest');
            $inventoryRequest->setQueryStartDateTime($this->date);
            $inventoryRequest->setSellerId($this->marketplace->getMerchantId());
            $inventoryResponse = $this->getClient()->listInventorySupply($inventoryRequest);
            if ($inventoryResponse->isSetListInventorySupplyResult()) {
                /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyResult $result */
                $result = $inventoryResponse->getListInventorySupplyResult();
                $this->processListInventorySupplyResult($result);
                $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
            }
        } catch (FBAInventoryServiceMWS_Exception $e) {
            return $this->handleError($e);
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    public function onSuccess()
    {
        $this->marketplace->setLastInventorySyncDate();
        parent::onSuccess();
    }

    public function toString()
    {
        return "Inventory sync by date {$this->date}."
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }
}
