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
class Webtex_FbaOrder_Model_Converter
{
    /**
     * Convert magento order address to FBA address.
     *
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @throws LogicException
     *
     * @return FBAOutboundServiceMWS_Model_Address
     */
    public function getShippingAddress(Mage_Sales_Model_Order_Address $address)
    {
        $fullStreetAddress = $address->getStreetFull();
        $company = $address->getCompany();
        if (!empty($company)) {
            $fullStreetAddress .= "\nCompany:" . $company;
        }

        $lines = str_split($fullStreetAddress, 60);

        if (count($lines) > 3) {
            throw new LogicException("Address is too long, only 3 lines with max length of 60 are allowed");
        }

        /** @var FBAOutboundServiceMWS_Model_Address $shippingAddress */
        $shippingAddress = Mage::getModel('mwsOut/model_address');
        $shippingAddress->setName($address->getName());
        $shippingAddress->setCity($address->getCity());
        $shippingAddress->setStateOrProvinceCode($address->getRegionCode());
        $shippingAddress->setCountryCode($address->getCountryModel()->getIso2Code());
        $shippingAddress->setPostalCode($address->getPostcode());
        $shippingAddress->setPhoneNumber($address->getTelephone());

        $number = 1;
        foreach ($lines as $line) {
            $setter = "setLine{$number}";
            $shippingAddress->$setter($line);
            $number++;
        }

        return $shippingAddress;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @param int $chunkId
     *
     * @return FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest
     */
    public function getFulfillmentOrder(Mage_Sales_Model_Order $order, $chunkId)
    {
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest $request */
        $request = Mage::getModel('mwsOut/model_createFulfillmentOrderRequest');
        $request->setDisplayableOrderId($order->getIncrementId());
        $request->setDisplayableOrderDateTime($order->getCreatedAtDate()->get(Zend_Date::ISO_8601));
        $request->setDisplayableOrderComment($order->getIncrementId());
        $request->setSellerFulfillmentOrderId($order->getIncrementId() . '_' . $chunkId);

        return $request;
    }

    /**
     * @param array $emails
     *
     * @return FBAOutboundServiceMWS_Model_NotificationEmailList
     */
    public function getNotificationEmails(array $emails)
    {
        /** @var FBAOutboundServiceMWS_Model_NotificationEmailList $emailList */
        $emailList = Mage::getModel('mwsOut/model_notificationEmailList');
        $emailList->setmember($emails);

        return $emailList;
    }

    /**
     * @param FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem[] $items
     *
     * @return FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItemList
     */
    public function getItemList($items)
    {
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItemList $requestItems */
        $requestItems = Mage::getModel('mwsOut/model_createFulfillmentOrderItemList');
        $requestItems->setmember($items);

        return $requestItems;

    }

    /**
     * @param $sku
     * @param $qty
     * @param $itemId
     *
     * @return FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem
     */
    public function getItem($sku, $qty, $itemId)
    {
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem $item */
        $item = Mage::getModel('mwsOut/model_createFulfillmentOrderItem');
        $item->setSellerSKU($sku);
        $item->setQuantity($qty);
        $item->setSellerFulfillmentOrderItemId($itemId);
        return $item;
    }
}