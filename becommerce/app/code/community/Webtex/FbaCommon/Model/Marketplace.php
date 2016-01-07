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


/**
 * amazon query model
 * Table: 'fba_mws_queries'
 * Fields:
 *  - id - primary key
 *  - status - enable/disable
 *  - access_key_id - 20 - character alphanumeric sequence
 *  - secret_key - 40 - character encoded sequence
 *  - merchant_id - merchant id
 *  - amazon_marketplace - int marketplace id from Webtex_Fba_Model_AmazonMarketplace model
 *
 * methods:
 * @method int getId()
 * @method Webtex_FbaCommon_Model_Marketplace setStatus(int)
 * @method int getStatus()
 * @method Webtex_FbaCommon_Model_Marketplace setAccessKeyId(string)
 * @method string getAccessKeyId()
 * @method Webtex_FbaCommon_Model_Marketplace setSecretKey(string)
 * @method string getSecretKey()
 * @method Webtex_FbaCommon_Model_Marketplace setPlainSecretKey(string)
 * @method Webtex_FbaCommon_Model_Marketplace setMerchantId(string)
 * @method string getMerchantId()
 * @method Webtex_FbaCommon_Model_Marketplace setAmazonMarketplace(int)
 * @method int getAmazonMarketplace()
 * @method Webtex_FbaCommon_Model_Marketplace setNotificationEmails(string)
 * @method string getNotificationEmails()
 * @method Webtex_FbaCommon_Model_Marketplace setNotifyCustomers(boolean)
 * @method boolean getNotifyCustomers()
 * @method Webtex_FbaCommon_Model_Marketplace setCarrierTitle(string)
 * @method string getCarrierTitle()
 * @method Webtex_FbaCommon_Model_Marketplace setSendOrderImmediately(boolean)
 * @method boolean getSendOrderImmediately()
 * @method Webtex_FbaCommon_Model_Marketplace setLastQueueExecutionTime(string)
 * @method string getLastQueueExecutionTime()
 * @method Webtex_FbaCommon_Model_Marketplace setNextQueueStartTime(string)
 * @method string getNextQueueStartTime()
 * @method Webtex_FbaCommon_Model_Marketplace setInventoryMode(int)
 * @method int getInventoryMode()
 * @method Webtex_FbaCommon_Model_Marketplace setShipOosAsNonFba(int)
 * @method int getShipOosAsNonFba()
 * @method Webtex_FbaCommon_Model_Marketplace setCheckQtyBeforePlaceOrder(boolean)
 * @method boolean getCheckQtyBeforePlaceOrder()
 * @method Webtex_FbaCommon_Model_Marketplace setQtyCheckField(string)
 * @method string getQtyCheckField()
 * @method Webtex_FbaCommon_Model_Marketplace setInventoryCheckFrequency(int)
 * @method int getInventoryCheckFrequency()
 * @method Webtex_FbaCommon_Model_Marketplace setCheckOrders(boolean)
 * @method boolean getCheckOrders()
 * @method Webtex_FbaCommon_Model_Marketplace setShippingCurrency(string)
 * @method string getShippingCurrency()
 * @method Webtex_FbaCommon_Model_Resource_Marketplace_Collection getCollection()
 *
 */

class Webtex_FbaCommon_Model_Marketplace extends Mage_Core_Model_Abstract
{
    protected $toEncrypt = array(
        'secret_key'
    );

    public function _construct()
    {
        parent::_construct();
        $this->_init('wfcom/marketplace');
    }

    protected function _beforeSave()
    {
        foreach ($this->toEncrypt as $attr) {
            $this->setData(
                $attr,
                $this->getEncryptor()->encrypt($this->getData($attr))
            );
        }
        if ($this->isObjectNew()) {
            if (!$this->getLastInventorySyncDate()) {
                $this->setLastInventorySyncDate();
            }
            if (!$this->getLastOrderSyncDate()) {
                $this->setLastOrderSyncDate();
            }

        }
        return parent::_beforeSave();
    }

    protected function _afterSave()
    {
        foreach ($this->toEncrypt as $attr) {
            $this->setData(
                $attr,
                $this->getEncryptor()->decrypt($this->getData($attr))
            );
        }
        $this->getCommonHelper()->invalidateMarketplaceCache();
        return parent::_afterSave();
    }

    protected function _afterLoad()
    {
        if ($this->getId()) {
            $codeArray = $this->getCommonHelper()->getEndpointSource()->toArray();
            $this->setCode($codeArray[$this->getAmazonMarketplace()] . "-" . $this->getId());
        }

        foreach ($this->toEncrypt as $attr) {
            $this->setData(
                $attr,
                $this->getEncryptor()->decrypt($this->getData($attr))
            );
        }

        return parent::_afterLoad();
    }



    protected function _afterDelete()
    {
        $this->getCommonHelper()->invalidateMarketplaceCache();
        return parent::_afterDelete();
    }


    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getCommonHelper()
    {
        return Mage::helper('wfcom');

    }

    /**
     * @return Webtex_Queue_Helper_Data
     */
    protected function getQueueHelper()
    {
        return Mage::helper('wqueue');

    }

    /**
     * @return Mage_Core_Model_Encryption
     */
    protected function getEncryptor()
    {
        return Mage::getModel('core/encryption');
    }



    public function getClientConfig($additionalUrl = '')
    {
        $urlArray = $this->getCommonHelper()->getEndpointSource()->toEndpointUrlArray();
        if (array_key_exists($this->getAmazonMarketplace(), $urlArray)) {
            return array('ServiceURL' => $urlArray[$this->getAmazonMarketplace()] . $additionalUrl);
        } else {
            return false;
        }
    }

    public function getLastInventorySyncDate()
    {
        $dateString = $this->getData('inventory_sync_last_date');
        if (!isset($dateString) || empty($dateString)) {
            return false;
        }
        $date = new DateTime($dateString, new DateTimeZone('UTC'));
        return $date->format('c');
    }

    public function setLastInventorySyncDate()
    {
        $date = new DateTime(null, new DateTimeZone('UTC'));
        $date->modify('-5 minute');
        return $this->setData('inventory_sync_last_date', $date->format("Y-m-d H:i:s"))->save();
    }

    public function getLastOrderSyncDate()
    {
        $dateString = $this->getData('orders_sync_last_date');
        if (!isset($dateString) || empty($dateString)) {
            return false;
        }
        $date = new DateTime($dateString, new DateTimeZone('UTC'));
        return $date->format('c');
    }

    public function setLastOrderSyncDate(DateTime $date = null)
    {
        if (!isset($date)) {
            $date = new DateTime(null, new DateTimeZone('UTC'));
        } else {
            $date = clone $date;
            $date->setTimezone(new DateTimeZone('UTC'));
        }
        return $this->setData('orders_sync_last_date', $date->format("Y-m-d H:i:s"))->save();
    }

    public function duplicate()
    {
        if ($this->getId()) {
            $newMarketplace = Mage::getModel('wfcom/marketplace');
            $currentData = $this->getData();
            unset($currentData['id']);
            unset($currentData['inventory_sync_last_date']);
            unset($currentData['orders_sync_last_date']);
            $newMarketplace->setData($currentData)->save();
            return $newMarketplace;

        }
        return false;
    }

    public function syncOrders()
    {
        if ($this->getStatus()) {
            /** @var Webtex_FbaOrder_Model_Task_DateSync $task */
            $task = Mage::getModel('wford/task_dateSync', $this);
            $this->getQueueHelper()->addJob($task);
        }
    }

    public function syncInventory()
    {
        if ($this->getStatus()) {
            /** @var Webtex_FbaInventory_Model_Resource_Stock $stockResource */
            $stockResource = Mage::getResourceModel('wfinv/stock');
            $skuToSync = $stockResource->getReadConnection()->select()
                ->from($stockResource->getMainTable())
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('link_sku')
                ->where('marketplace_key = ?', $this->getId())
                ->distinct();
            $skuToSync = $stockResource->getReadConnection()->fetchCol($skuToSync);
            if (count($skuToSync)) {
                /** @var Webtex_FbaInventory_Model_Task_ListSync $task */
                $task = Mage::getModel('wfinv/task_listSync', $this);
                $task->initSkuList($skuToSync);
                $this->getQueueHelper()->addJob($task);

            }
        }

    }
}
