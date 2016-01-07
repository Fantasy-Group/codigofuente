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
class Webtex_FbaInventory_Model_Task_ListSync extends Webtex_FbaInventory_Model_Task_Abstract
{
    const MAX_SKU_COUNT = 50;

    protected $skuList;

    protected $chunkedList;

    private $chudkedListPage = 0;

    public function initSkuList($list)
    {
        if (isset($this->skuList)) {
            throw new LogicException('Sku list is already initialized');
        }

        $this->skuList = $list;

    }

    public function work()
    {
        while (true) {
            $chunk = $this->getCurrentChunk();
            if ($chunk === false) {
                $this->status = Webtex_Queue_Model_Job::STATUS_DONE;
                break;
            } else {
                try {
                    /* wrap up chunk into amazon format */
                    $chunk = array('member' => $chunk);
                    /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyRequest $inventoryRequest */
                    $inventoryRequest = $this->getCommonHelper()->getModel('mwsInv/model_listInventorySupplyRequest');
                    $inventoryRequest->setSellerSkus(
                        $this->getCommonHelper()->getModel('mwsInv/model_sellerSkuList', array($chunk))
                    );
                    $inventoryRequest->setSellerId($this->marketplace->getMerchantId());
                    $inventoryResponse = $this->getClient()->listInventorySupply($inventoryRequest);
                    if ($inventoryResponse->isSetListInventorySupplyResult()) {
                        /** @var FBAInventoryServiceMWS_Model_ListInventorySupplyResult $result */
                        $result = $inventoryResponse->getListInventorySupplyResult();
                        $this->processListInventorySupplyResult($result);
                    }
                    $this->doChunk();
                } catch (FBAInventoryServiceMWS_Exception $e) {
                    return $this->handleError($e);
                }
            }
        }

        return Webtex_Queue_Model_Job::RESULT_OK;
    }

    private function getCurrentChunk()
    {
        if (!isset($this->skuList)) {
            throw new LogicException("You should initialize sku list with initSkuList function");
        }

        $chunk = false;
        if (!isset($this->chunkedList)) {
            $this->chunkedList = array_chunk($this->skuList, self::MAX_SKU_COUNT);
        }

        if (isset($this->chunkedList[$this->chudkedListPage])) {
            $chunk = $this->chunkedList[$this->chudkedListPage];
            if (empty($chunk)) {
                $chunk = false;
            }
        }

        return $chunk;
    }

    private function doChunk()
    {
        $this->chudkedListPage += 1;
    }

    public function toString()
    {
        $skuCount = count($this->skuList);

        return "Inventory sync by Seller SKU list job"
        . " with {$skuCount} items in list.\n"
        . " Marketplace: "
        . $this->getCommonHelper()->getMarketplaceLabel($this->marketplace->getId());
    }

    public function onSuccess()
    {
        /** @var Varien_Db_Adapter_Pdo_Mysql $writeResource */
        $writeResource = Mage::getSingleton('core/resource')->getConnection('core_write');
        /** @var Webtex_FbaInventory_Model_Resource_Stock $stockResource */
        $stockResource = Mage::getResourceModel('wfinv/stock');
        $writeResource->update(
            $stockResource->getMainTable(),
            array(
                'is_sync_required' => 0
            ),
            array(
                'link_sku in (?)' => $this->skuList,
                'marketplace_key = ?' => $this->marketplace->getId()
            )
        );
        return parent::onSuccess();
    }


}