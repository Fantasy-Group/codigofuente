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
 * Class Webtex_FbaOrder_Model_Order
 * @method Webtex_FbaOrder_Model_Order setEntityId(int)
 * @method Webtex_FbaOrder_Model_Order setMageOrderKey(int)
 * @method Webtex_FbaOrder_Model_Order setMarketplaceKey(int)
 * @method Webtex_FbaOrder_Model_Order setSellerFulfillmentOrderId(string)
 * @method Webtex_FbaOrder_Model_Order setRequest(FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest $request)
 * @method Webtex_FbaOrder_Model_Order setOrder(FBAOutboundServiceMWS_Model_FulfillmentOrder $order)
 * @method Webtex_FbaOrder_Model_Order setItem(FBAOutboundServiceMWS_Model_FulfillmentOrderItemList $item)
 * @method Webtex_FbaOrder_Model_Order setShipment(FBAOutboundServiceMWS_Model_FulfillmentShipmentList $shipment)
 * @method Webtex_FbaOrder_Model_Order setShipmentMap(array $shipmentMap)
 * @method Webtex_FbaOrder_Model_Order setTracks(array $tracks)
 * @method Webtex_FbaOrder_Model_Order setDisplayableOrderId(string $id)
 * @method Webtex_FbaOrder_Model_Order setDisplayableOrderDate(string $date)
 * @method Webtex_FbaOrder_Model_Order setReceivedDate(string $date)
 * @method Webtex_FbaOrder_Model_Order setStatusUpdatedDate(string $date)
 * @method Webtex_FbaOrder_Model_Order setShippingSpeedCategory(string $category)
 * @method Webtex_FbaOrder_Model_Order setFulfillmentOrderStatus(string $status)
 * @method Webtex_FbaOrder_Model_Order setInternalStatus(int $status)
 * @method Webtex_FbaOrder_Model_Order setErrorMessages(string $messages)
 * @method int getEntityId()
 * @method int getMageOrderKey()
 * @method int getMarketplaceKey()
 * @method string getSellerFulfillmentOrderId()
 * @method FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest getRequest()
 * @method FBAOutboundServiceMWS_Model_FulfillmentOrder getOrder()
 * @method FBAOutboundServiceMWS_Model_FulfillmentOrderItemList getItem()
 * @method FBAOutboundServiceMWS_Model_FulfillmentShipmentList getShipment()
 * @method array getShipmentMap()
 * @method FBAOutboundServiceMWS_Model_GetPackageTrackingDetailsResult[] getTracks()
 * @method string getDisplayableOrderId()
 * @method string getDisplayableOrderDate()
 * @method string getReceivedDate()
 * @method string getStatusUpdatedDate()
 * @method string getShippingSpeedCategory()
 * @method string getFulfillmentOrderStatus()
 * @method int getInternalStatus()
 * @method string getErrorMessages()
 */
class Webtex_FbaOrder_Model_Order
    extends Mage_Core_Model_Abstract
{

    const STATUS_PENDING = 0;
    const STATUS_IN_QUEUE = 1;
    const STATUS_PLACED = 2;
    const STATUS_ERROR = 3;

    private $serializeAttrList = array(
        'request',
        'order',
        'item',
        'shipment',
        'shipment_map',
        'tracks'
    );

    protected function _construct()
    {
        parent::_construct();
        $this->_init('wford/order');
    }

    private function adjustDate($source, $sourceAttr, $destinationAttr)
    {
        $checker = "isSet{$sourceAttr}";
        $getter = "get{$sourceAttr}";
        if ($source->$checker()) {
            $dateValue = DateTime::createFromFormat(
                DateTime::ISO8601,
                $source->$getter()
            )
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
            if ($dateValue != $this->getData($destinationAttr)) {
                $this->setData($destinationAttr, $dateValue);
            }
        }

        return $this;
    }

    protected function _beforeSave()
    {
        if ($this->getRequest()) {
            $this->adjustDate($this->getRequest(), 'DisplayableOrderDateTime', 'displayable_order_date');
            if (!$this->getDisplayableOrderId()) {
                $this->setDisplayableOrderId($this->getRequest()->getDisplayableOrderId());
            }
            if (!$this->getShippingSpeedCategory()) {
                $this->setShippingSpeedCategory($this->getRequest()->getShippingSpeedCategory());
            }
        }
        if ($this->getOrder()) {
            if ($this->getFulfillmentOrderStatus() != $this->getOrder()->getFulfillmentOrderStatus()) {
                $this->setFulfillmentOrderStatus($this->getOrder()->getFulfillmentOrderStatus());
            }
            $this->adjustDate($this->getOrder(), 'ReceivedDateTime', 'received_date');
            $this->adjustDate($this->getOrder(), 'StatusUpdatedDateTime', 'status_updated_date');
        }

        if ($this->isObjectNew() && !$this->getInternalStatus()) {
            $this->setInternalStatus(self::STATUS_PENDING);
        }

        $this->serializeAttr();

        return parent::_beforeSave();
    }

    protected function _afterLoad()
    {
        $this->unserializeAttr();

        return parent::_afterLoad();
    }

    protected function _afterSave()
    {
        $this->unserializeAttr();

        return parent::_afterSave();
    }

    protected function serializeAttr()
    {
        foreach ($this->serializeAttrList as $attr) {
            if (!empty($this->_data[$attr])) {
                $this->_data[$attr] = serialize($this->_data[$attr]);
                if (!isset($this->_origData[$attr])
                    && $this->_data[$attr] != $this->_origData[$attr]
                ) {
                    $this->_hasDataChanges = true;
                }
            }
        }

        return $this;
    }

    protected function _hasModelChanged()
    {
        foreach ($this->serializeAttrList as $attr) {
            if (!empty($this->_data[$attr])) {
                $newValue = serialize($this->_data[$attr]);
                if (!isset($this->_origData[$attr])
                    || $newValue != $this->_origData[$attr]
                ) {
                    $this->_hasDataChanges = true;
                    break;
                }
            }
        }
        return parent::_hasModelChanged();
    }

    protected function unserializeAttr()
    {
        foreach ($this->serializeAttrList as $attr) {
            if (!empty($this->_data[$attr])) {
                $this->_data[$attr] = unserialize($this->_data[$attr]);
            }
        }

        return $this;

    }


}