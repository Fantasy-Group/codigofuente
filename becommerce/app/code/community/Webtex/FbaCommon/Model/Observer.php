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

class Webtex_FbaCommon_Model_Observer
{
    public function checkOrder()
    {
        /** @var Webtex_FbaCommon_Model_Marketplace[] $marketplaces */
        $marketplaces = Mage::getModel('wfcom/marketplace')->getCollection()
            ->addFieldToFilter('status', 1);
        foreach ($marketplaces as $marketplace) {
            $task = Mage::getModel('wford/task_dateSync', $marketplace);
            $this->getQueueHelper()->addJob($task);
        }
    }

    public function checkInventory()
    {
        /** @var Webtex_FbaCommon_Model_Marketplace[] $marketplaces */
        $marketplaces = Mage::getModel('wfcom/marketplace')->getCollection()
            ->addFieldToFilter('status', 1);
        foreach ($marketplaces as $marketplace) {
            /** @var Webtex_FbaInventory_Model_Resource_Stock $stockResource */
            $stockResource = Mage::getResourceModel('wfinv/stock');
            $select = $stockResource->getReadConnection()->select()
                ->from($stockResource->getMainTable(), 'link_sku')
                ->where('marketplace_key=?', $marketplace->getId())
                ->where('is_sync_required=1');
            $skusToInit = $stockResource->getReadConnection()
                ->fetchCol($select);
            if ($skusToInit && count($skusToInit)) {
                /** @var Webtex_FbaInventory_Model_Task_ListSync $task */
                $task = Mage::getModel('wfinv/task_listSync', $marketplace);
                $task->initSkuList($skusToInit);
                $this->getQueueHelper()->addJob($task);
            }
            /** @var Webtex_FbaInventory_Model_Task_DateSync $task */
            $task = Mage::getModel('wfinv/task_dateSync', $marketplace);
            $this->getQueueHelper()->addJob($task);
        }
    }

    public function cleanJobs()
    {
        /** @var Varien_Db_Adapter_Pdo_Mysql $writeResource */
        $writeResource = Mage::getSingleton('core/resource')->getConnection('core_write');
        /** @var Webtex_Queue_Model_Resource_Job $jobResource */
        $jobResource = Mage::getResourceModel('wqueue/job');
        $writeResource->delete(
            $jobResource->getMainTable(),
            array(
                'status in (?)' => array(
                    Webtex_Queue_Model_Job::STATUS_DONE,
                    Webtex_Queue_Model_Job::STATUS_ERROR
                ),
                'created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)'
            )
        );
    }

    public function cleanStock()
    {
        /** @var Varien_Db_Adapter_Pdo_Mysql $writeResource */
        $writeResource = Mage::getSingleton('core/resource')->getConnection('core_write');
        /** @var Webtex_FbaInventory_Model_Resource_Stock $stockRes */
        $stockRes = Mage::getResourceModel('wfinv/stock');
        /** @var Webtex_FbaInventory_Model_Resource_Link $linkRes */
        $linkRes = Mage::getResourceModel('wfinv/link');
        $query = "DELETE  stock.* " .
            "FROM {$stockRes->getMainTable()} AS stock " .
            "LEFT JOIN " .
            " {$linkRes->getMainTable()} AS link " .
            "ON link.stock_key = stock.entity_id " .
            "WHERE  link.stock_key IS NULL ";
        $writeResource->query($query);

    }

    public function orderInvoiced($observer)
    {
        if ($this->getCommonHelper()->getAutosendMode()
            == Webtex_FbaOrder_Model_Config_Mode::WHEN_INVOICED
        ) {
            try {
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                $invoice = $observer->getEvent()->getInvoice();
                if ($invoice
                    && $invoice instanceof Mage_Sales_Model_Order_Invoice
                ) {
                    $order = $invoice->getOrder();
                    if ($order->getBaseTotalDue() == 0) {
                        $this->parseOrder($order);
                    }
                }
            } catch (Exception $e) {
                $this->getCommonHelper()->log($e, Zend_Log::ERR);
            }
        }
    }

    public function productSaveAfter($observer)
    {
        try {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getEvent()->getProduct();
            if ($product && $product instanceof Mage_Catalog_Model_Product) {
                /** @var Webtex_FbaInventory_Model_Product $productHelper */
                $productHelper = Mage::getModel('wfinv/product', $product);
                $productHelper->checkLocalStock();
            }
        } catch (Exception $e) {
            $this->getCommonHelper()->log($e, Zend_Log::ERR);
        }
    }

    public function orderPlaced($observer)
    {
        if ($this->getCommonHelper()->getAutosendMode()
            == Webtex_FbaOrder_Model_Config_Mode::WHEN_CREATED
        ) {
            try {
                /** @var $order Mage_Sales_Model_Order */
                $order = $observer->getEvent()->getOrder();
                if ($order && $order instanceof Mage_Sales_Model_Order) {
                    $this->parseOrder($order);
                }
            } catch (Exception $e) {
                $this->getCommonHelper()->log($e, Zend_Log::ERR);

            }
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function parseOrder($order)
    {
        $alreadyParsed = Mage::getModel('wford/order')->getCollection()
            ->addFieldToFilter('mage_order_key', $order->getEntityId())
            ->count();
        if (!$alreadyParsed) {
            /** @var Webtex_FbaOrder_Model_OrderParser $parser */
            $parser = Mage::getModel('wford/orderParser', $order);
            $fulfillmentOrder = $parser->getOrderRequests();
            if ($fulfillmentOrder) {
                /** @var Webtex_FbaOrder_Model_Task_Create $task */
                $task = Mage::getModel('wford/task_create', $fulfillmentOrder->getMarketplaceKey());
                $task->initOrderRequest($fulfillmentOrder);
                $this->getQueueHelper()->addJob($task);
            }
        }
    }

    public function sendQueue()
    {
        $this->getQueueHelper()->runQueue();
    }

    /**
     * @return Webtex_Queue_Helper_Data
     */
    protected function  getQueueHelper()
    {
        return Mage::helper('wqueue');
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function  getCommonHelper()
    {
        return Mage::helper('wfcom');
    }
}