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
class Webtex_FbaOrder_FulfillmentOrderController extends Mage_Adminhtml_Controller_Action
{
    public function ordersAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fba_tab')
            ->_title($this->__('Fulfillment Orders'));
        $this->renderLayout();
    }

    public function ordersGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('wford/orders_grid')->toHtml()
        );
    }

    public function viewOrderAction()
    {
        $this->_title('Amazon')
            ->_title('Fulfillment Order');

        // 1. Get Fulfillment order model
        $fOrderId = $this->getRequest()->getParam('id');
        /** @var Webtex_FbaOrder_Model_Order $fOrder */
        $fOrder = Mage::getModel('wford/order')->load($fOrderId);
        if ($fOrderId && !$fOrder->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('wford')->__('Unknown fulfillment order ID')
            );
            $this->_redirect('*/*/orders');

            return;
        }

        // 2. Register model to use later in blocks
        Mage::register('fulfillment_order', $fOrder);
        $this->loadLayout()
            ->_setActiveMenu('fba_tab');
        $this->renderLayout();
    }

    public function createOrderAction()
    {
        Mage::getSingleton('core/session')->unsFbaOrder();
        $this->_title('Amazon')
            ->_title('Fulfillment Order');

        $this->loadLayout()
            ->_setActiveMenu('fba_tab');
        $this->renderLayout();
    }

    public function editOrderAction()
    {
        $this->_title('Amazon')
            ->_title('Fulfillment Order');

        $this->loadLayout()
            ->_setActiveMenu('fba_tab');
        $this->renderLayout();
    }

    public function saveOrderAction()
    {
        $this->_title('Amazon')
            ->_title('Fulfillment Order');
        $formData = $this->getRequest()->getParams();
        $errors = array();
        if (isset($formData['mage_order'])) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($formData['mage_order']);

            if (!$order || !$order->getEntityId()) {
                unset($order);
            }
        }
        $marketplaceId = $formData['marketplace'];
        /** @var Webtex_FbaCommon_Model_Marketplace $marketplace */
        $marketplace = Mage::getModel('wfcom/marketplace')->load($marketplaceId);
        if (!$marketplace || !$marketplace->getId()) {
            $errors[] = 'Marketplace not found.';
        } elseif ($marketplace->getStatus() == 0) {
            $errors[] = 'Marketplace not active.';
        } elseif ($formData['submit-mode'] == 'parse') {
            if (!isset($order)) {
                $errors[] = 'Order is not found.';
            } elseif (!count($errors)) {
                $formData = $this->parseOrder($marketplace, $order, $formData);
                if (!count($formData['order_items'])) {
                    $errors[] = "No items to fulfill found in this order";
                }
            }
            $this->editRedirect($formData, $errors);
        } elseif ($formData['submit-mode'] == 'preview') {
            $formData['order_items'] = $this->groupItems($formData['order_items'], $marketplace->getId());
            $this->editRedirect($formData, $errors);

        } elseif ($formData['submit-mode'] == 'save') {
            /** @var Webtex_FbaOrder_Model_Order $fOrder */
            $fOrder = $this->createFulfillmentOrder($formData, $marketplace, $order);
            /** @var Webtex_FbaOrder_Model_Task_Create $task */
            $task = Mage::getModel('wford/task_create', $marketplace);
            $task->initOrderRequest($fOrder);
            /** @var Webtex_Queue_Helper_Data $helper */
            $helper = Mage::helper('wqueue');
            $helper->addJob($task);
            if (count($errors)) {
                $this->editRedirect($formData, $errors);
            }
            $this->_redirect('*/*/orders');
        }
    }

    private function getItem($marketplaceId, $sku, $qty)
    {
        /** @var Webtex_FbaInventory_Model_ProductMass $productMass */
        $productMass = Mage::getSingleton('wfinv/productMass');
        $levels = $productMass->getStockLevels(
            $marketplaceId,
            $sku
        );
        $item = array(
            'seller_sku' => $sku,
            'qty' => $qty
        );
        if ($levels) {
            $item['amazon_qty'] =
                "<span style='color:green'>{$levels['in_stock_qty']}</span>/"
                . "<span style='color:orange'>{$levels['total_qty']}</span>/"
                . "<span style='color:red'>{$levels['blocked_qty']}</span>";

        } else {
            $item['amazon_qty'] =
                "<span style='color:red'>Not linked</span>";
        }

        return $item;
    }

    public function groupItems($orderItems, $marketplaceId)
    {
        $items = array();
        foreach ($orderItems as $item) {
            if (isset($item['seller_sku']) && !empty($item['seller_sku'])) {
                if (!isset($items[$item['seller_sku']])) {
                    $items[$item['seller_sku']] = 0;
                }
                if (isset($item['qty']) && !empty($item['qty']) && is_numeric($item['qty'])) {
                    $items[$item['seller_sku']] += $item['qty'];
                }
            }
        }
        $formData = array();
        foreach ($items as $sku => $qty) {
            $formData[] = $this->getItem($marketplaceId, $sku, $qty);
        }

        return $formData;
    }

    private function editRedirect($formData, $errors)
    {
        $this->publishErrors($errors);
        Mage::getSingleton('core/session')->setFbaOrder($formData);
        $this->_redirect('*/*/editOrder');
    }

    private function publishErrors($errors)
    {
        foreach ($errors as $error) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('wford')->__($error)
            );
        }
    }

    /**
     * @param Webtex_FbaCommon_Model_Marketplace $marketplace
     * @param Mage_Sales_Model_Order $order
     *
     * @throws LogicException
     *
     * @return array
     */
    private function parseOrder($marketplace, $order, $formData)
    {
        /** @var Webtex_FbaOrder_Model_OrderParser $parser */
        $parser = Mage::getModel('wford/orderParser', $order);
        $result = array();
        $result['marketplace'] = $marketplace->getId();
        $result['mage_order'] = $order->getIncrementId();
        $result['order_items'] = $this->groupItems($parser->parseForMarketplace($marketplace), $marketplace->getId());
        if (!$result['order_items']) {
            $result['order_items'] = array();
        }
        $result['displayable_order_id'] = !empty($formData['displayable_order_id'])
            ? $formData['displayable_order_id'] : $order->getIncrementId();
        $result['displayable_order_comment'] = !empty($formData['displayable_order_comment'])
            ? $formData['displayable_order_comment'] : $order->getIncrementId();
        $result['shipping_speed_category'] = !empty($formData['shipping_speed_category'])
            ? $formData['shipping_speed_category'] : 'standard';
        $result['displayable_order_date_time'] = Mage::helper('core')
            ->formatDate($order->getCreatedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_SHORT, true);

        $result['shipping_speed_category'] = !empty($formData['notification_email_list'])
            ? $formData['notification_email_list'] : '';

        $fullStreetAddress = $order->getShippingAddress()->getStreetFull();
        $company = $order->getShippingAddress()->getCompany();
        if (!empty($company)) {
            $fullStreetAddress .= "\nCompany:" . $company;
        }

        $lines = str_split($fullStreetAddress, 60);

        if (count($lines) > 3) {
            throw new LogicException("Address is too long, only 3 lines with max length of 60 are allowed");
        }

        $result['street'] = implode(',', $lines);
        $result['name'] = $order->getShippingAddress()->getName();
        $result['city'] = $order->getShippingAddress()->getCity();
        $result['region'] = $order->getShippingAddress()->getRegion();
        $result['region_code'] = $order->getShippingAddress()->getRegionId();
        $result['country_code'] = $order->getShippingAddress()->getCountryModel()->getIso2Code();
        $result['postcode'] = $order->getShippingAddress()->getPostcode();
        $result['telephone'] = $order->getShippingAddress()->getTelephone();

        return $result;
    }

    private function createFulfillmentOrder($formData, $marketplace, $order = null)
    {
        /** @var Webtex_FbaOrder_Model_Order $fOrder */
        $fOrder = Mage::getModel('wford/order');
        if ($order) {
            $fOrder->setMageOrderKey($fOrder->getEntityId());
        }
        $fOrder->setMarketplaceKey($marketplace->getId());
        $fId = $fOrder->save()->getId();
        $sellerOrderId = isset($order) ? $order->getIncrementId() : 'xxx';
        $sellerOrderId .= '_' . $fId;
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderRequest $request */
        $request = Mage::getModel('mwsOut/model_createFulfillmentOrderRequest');
        $request->setDisplayableOrderId($formData['displayable_order_id']);
        $displayableDateTime = Mage::app()->getLocale()->date(
            $formData['displayable_order_date_time'],
            Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        )->get(Zend_Date::ISO_8601);
        $request->setDisplayableOrderDateTime($displayableDateTime);
        $request->setDisplayableOrderComment($formData['displayable_order_comment']);
        $request->setSellerFulfillmentOrderId($sellerOrderId);
        $request->setSellerId($marketplace->getMerchantId());
        $request->setShippingSpeedCategory($formData['shipping_speed_category']);
        /** @var FBAOutboundServiceMWS_Model_NotificationEmailList $emailList */
        $emailList = Mage::getModel('mwsOut/model_notificationEmailList');
        $emailList->setmember(explode(',', $formData['notification_email_list']));
        $request->setNotificationEmailList($emailList);
        $itemNumber = 1;
        $items = array();
        foreach ($formData['order_items'] as $item) {
            /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem $fItem */
            $fItem = Mage::getModel('mwsOut/model_createFulfillmentOrderItem');
            $fItem->setSellerSKU($item['seller_sku']);
            $fItem->setQuantity($item['qty']);
            $fItem->setSellerFulfillmentOrderItemId($itemNumber);
            $items[] = $fItem;
            $itemNumber += 1;
        }
        /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItemList $requestItems */
        $requestItems = Mage::getModel('mwsOut/model_createFulfillmentOrderItemList');
        $requestItems->setmember($items);
        $request->setItems($requestItems);
        /** @var FBAOutboundServiceMWS_Model_Address $shippingAddress */
        $shippingAddress = Mage::getModel('mwsOut/model_address');
        $shippingAddress->setName($formData['name']);
        $shippingAddress->setCity($formData['city']);
        /** @var Mage_Directory_Model_Country $countryModel */
        $countryModel = Mage::getModel('directory/country')->load($formData['country_code']);
        if (count($countryModel->getRegions()->getItems())) {
            /** @var Mage_Directory_Model_Region $region */
            $region = Mage::getModel('directory/region')->load($formData['region_code']);
            $region = $region->getCode();
        } else {
            $region = $formData['region'];
        }
        $shippingAddress->setStateOrProvinceCode($region);
        $shippingAddress->setCountryCode($countryModel->getIso2Code());
        $shippingAddress->setPostalCode($formData['postcode']);
        $shippingAddress->setPhoneNumber($formData['telephone']);
        $shippingAddress->setLine1($formData['street'][0]);
        $shippingAddress->setLine2($formData['street'][1]);
        $shippingAddress->setLine3($formData['street'][2]);
        $request->setDestinationAddress($shippingAddress);
        $fOrder->setRequest($request);
        $fOrder->setSellerFulfillmentOrderId($request->getSellerFulfillmentOrderId());
        return $fOrder->save();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fba_tab/fulfillment_orders');
    }
}
