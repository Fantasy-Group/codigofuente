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
class Webtex_FbaOrder_Block_Orders_View_Form extends Webtex_FbaCommon_Block_FormWithGrid
{
    /** @var Varien_Data_Form */
    protected $form;

    protected function _prepareForm()
    {
        $this->form = new Varien_Data_Form();
        $fieldSet = $this->form->addFieldset(
            'order_info',
            array(
                'legend' => Mage::helper('wford')->__('Amazon Fulfillment Order Details')
            )
        );

        $fieldSet->addField(
            'marketplace',
            'text',
            array(
                'name' => 'marketplace',
                'label' => Mage::helper('wford')->__('Amazon Marketplace'),
                'readonly' => true
            )
        );

        $mageOrder = $this->getMagentoOrder();
        if ($mageOrder) {
            $fieldSet->addField(
                'magento_order',
                'link',
                array(
                    'name' => 'magento_order',
                    'label' => Mage::helper('wford')->__('Magento Order'),
                    'value' => $mageOrder->getIncrementId(),
                    'href' => Mage::helper('adminhtml')->getUrl(
                        "adminhtml/sales_order/view",
                        array('order_id' => $mageOrder->getId())
                    )
                )
            );
        }

        $fieldSet->addField(
            'seller_id',
            'text',
            array(
                'name' => 'seller_id',
                'label' => Mage::helper('wford')->__('Seller Fulfillment Order ID'),
                'readonly' => true
            )
        );

        $fieldSet->addField(
            'displayable_id',
            'text',
            array(
                'name' => 'displayable_id',
                'label' => Mage::helper('wford')->__('Displayable Order ID'),
                'readonly' => true
            )
        );

        if ($this->getFOrder()->getFulfillmentOrderStatus()) {
            $fieldSet->addField(
                'status',
                'text',
                array(
                    'name' => 'status',
                    'label' => Mage::helper('wford')->__('Fulfillment Order Status'),
                    'readonly' => true
                )
            );
        }

        $fieldSet->addField(
            'internal_status',
            'text',
            array(
                'name' => 'internal_status',
                'label' => Mage::helper('wford')->__('Fulfillment Order Status'),
                'readonly' => true
            )
        );

        if ($this->getFOrder()->getInternalStatus() == Webtex_FbaOrder_Model_Order::STATUS_ERROR) {
            $fieldSet->addField(
                'error_messages',
                'text',
                array(
                    'name' => 'error_messages',
                    'label' => Mage::helper('wford')->__('Error Messages'),
                    'readonly' => true
                )
            );

        }

        $fieldSet->addField(
            'speed',
            'text',
            array(
                'name' => 'speed',
                'label' => Mage::helper('wford')->__('Fulfillment Order Speed Category'),
                'readonly' => true
            )
        );


        $fieldSet->addField(
            'displayable_date',
            'text',
            array(
                'name' => 'displayable_date',
                'label' => Mage::helper('wford')->__('Displayable Order Date'),
                'readonly' => true
            )
        );

        if ($this->getFOrder()->getReceivedDate()) {
            $fieldSet->addField(
                'received_date',
                'text',
                array(
                    'name' => 'received_date',
                    'label' => Mage::helper('wford')->__('Order Received Date'),
                    'readonly' => true
                )
            );
        }

        if ($this->getFOrder()->getStatusUpdatedDate()) {
            $fieldSet->addField(
                'status_date',
                'text',
                array(
                    'name' => 'status_date',
                    'label' => Mage::helper('wford')->__('Status Updated Date'),
                    'readonly' => true
                )
            );
        }
        $this->addGridField(
            $fieldSet,
            'order_items',
            array(
                'name' => 'order_items',
                'label' => Mage::helper('wford')->__('Order Items'),
                'readonly' => true
            )
        );

        $this->addGridField(
            $fieldSet,
            'order_shipments',
            array(
                'name' => 'order_shipments',
                'label' => Mage::helper('wford')->__('Order Shipments'),
                'readonly' => true
            )
        );

        $this->form->setMethod('post');
        $this->form->setUseContainer(true);
        $this->form->setId('orders_view');
        $this->form->addValues($this->prepareView()->getData());
        $this->setForm($this->form);

        return parent::_prepareForm();
    }

    /**
     * @return Varien_Object
     */
    private function prepareView()
    {
        $view = new Varien_Object();
        $view->setMarketplace($this->getCommonHelper()->getMarketplaceLabel($this->getFOrder()->getMarketplaceKey()));
        $view->setSellerId($this->getFOrder()->getSellerFulfillmentOrderId());
        $view->setDisplayableId($this->getFOrder()->getDisplayableOrderId());
        $view->setDisplayableDate($this->getFOrder()->getDisplayableOrderDate());
        $view->setStatus($this->getFOrder()->getFulfillmentOrderStatus());
        $view->setSpeed($this->getFOrder()->getShippingSpeedCategory());
        $view->setStatusDate($this->getFOrder()->getStatusUpdatedDate());
        $view->setReceivedDate($this->getFOrder()->getReceivedDate());
        $view->setOrderItems($this->getOrderItems());
        $view->setOrderShipments($this->getOrderShipments());
        $view->setInternalStatus(Mage::helper('wford')->getInternalStatusLabel($this->getFOrder()->getInternalStatus()));
        $view->setErrorMessages($this->getFOrder()->getErrorMessages());

        return $view;
    }

    private function getOrderItems()
    {
        $result = array();
        if ($this->getFOrder()->getItem()) {
            foreach ($this->getFOrder()->getItem()->getmember() as $item) {
                /** @var FBAOutboundServiceMWS_Model_FulfillmentOrderItem $item */
                $fieldSet = array(
                    'seller_sku' => $this->getTextElement($item->getSellerSKU()),
                    'seller_fulfillment_order_item_id' =>
                        $this->getTextElement($item->getSellerFulfillmentOrderItemId()),
                    'qty' => $this->getTextElement($item->getQuantity()),
                    'canceled_qty' => $this->getTextElement($item->getCancelledQuantity()),
                    'unfulfillable_qty' => $this->getTextElement($item->getUnfulfillableQuantity()),
                );
                if ($item->isSetEstimatedArrivalDateTime()) {
                    $fieldSet['estimated_arrival_date'] = $this->getTextElement(
                        DateTime::createFromFormat(
                            DateTime::ISO8601,
                            $item->getEstimatedArrivalDateTime()
                        )->setTimezone(new DateTimeZone(Mage::app()->getLocale()->getTimezone()))
                            ->format('Y-m-d H:i:s')
                    )->setStyle("width:110px");
                } else {
                    $fieldSet['estimated_arrival_date'] = $this->getTextElement("")
                        ->setStyle("width:110px");
                }
                if ($item->isSetEstimatedShipDateTime()) {
                    $fieldSet['estimated_ship_date'] = $this->getTextElement(
                        DateTime::createFromFormat(
                            DateTime::ISO8601,
                            $item->getEstimatedShipDateTime()
                        )->setTimezone(new DateTimeZone(Mage::app()->getLocale()->getTimezone()))
                            ->format('Y-m-d H:i:s')
                    )->setStyle("width:110px");
                } else {
                    $fieldSet['estimated_ship_date'] = $this->getTextElement("")
                        ->setStyle("width:110px");
                }

                $result['collection'][] = $fieldSet;
            }

            $result['columns'] = array(
                'seller_sku' => 'Seller Sku',
                'seller_fulfillment_order_item_id' => 'Fulfillment Order Item ID',
                'qty' => 'Qty',
                'canceled_qty' => 'Canceled Qty',
                'unfulfillable_qty' => 'Unfulfillable Qty',
                'estimated_arrival_date' => 'Estimated Arrival Date',
                'estimated_ship_date' => 'Estimated Ship Date'
            );

        } elseif ($this->getFOrder()->getRequest()->getItems()) {
            foreach ($this->getFOrder()->getRequest()->getItems()->getmember() as $item) {
                /** @var FBAOutboundServiceMWS_Model_CreateFulfillmentOrderItem $item */
                $fieldSet = array(
                    'seller_sku' => $this->getTextElement($item->getSellerSKU()),
                    'seller_fulfillment_order_item_id' =>
                        $this->getTextElement($item->getSellerFulfillmentOrderItemId()),
                    'qty' => $this->getTextElement($item->getQuantity()),
                );

                $result['collection'][] = $fieldSet;
            }

            $result['columns'] = array(
                'seller_sku' => 'Seller Sku',
                'seller_fulfillment_order_item_id' => 'Fulfillment Order Item ID',
                'qty' => 'Qty',
            );

        }

        return $result;
    }

    private function getOrderShipments()
    {
        $result = array();
        if ($this->getFOrder()->getShipment()) {
            foreach ($this->getFOrder()->getShipment()->getmember() as $shipment) {
                /** @var FBAOutboundServiceMWS_Model_FulfillmentShipment $shipment */
                if ($shipment->isSetFulfillmentShipmentItem()) {
                    foreach ($shipment->getFulfillmentShipmentItem()->getmember() as $shipmentItem) {
                        /** @var FBAOutboundServiceMWS_Model_FulfillmentShipmentItem $shipmentItem */
                        $fieldSet = array(
                            'seller_sku' => $this->getTextElement($shipmentItem->getSellerSKU()),
                            'qty' => $this->getTextElement($shipmentItem->getQuantity()),
                            'status' => $this->getTextElement($shipment->getFulfillmentShipmentStatus()),
                            'shipment_id' => $this->getTextElement($shipment->getAmazonShipmentId()),
                        );
                        $result['collection'][] = $fieldSet;
                    }
                }
            }

            $result['columns'] = array(
                'seller_sku' => 'Seller Sku',
                'qty' => 'Qty',
                'status' => 'Fulfillment Shipment Status',
                'shipment_id' => 'Amazon Shipment ID'
            );

        }

        return $result;
    }

    /**
     * @return Webtex_FbaOrder_Model_Order
     */
    private function getFOrder()
    {
        return Mage::registry('fulfillment_order');
    }

    /**
     * @return bool|Mage_Sales_Model_Order
     */
    private function getMagentoOrder()
    {
        if ($this->getFOrder()->getMageOrderKey()) {
            $order = Mage::getModel('sales/order')->load($this->getFOrder()->getMageOrderKey());
            if ($order && $order->getId()) {
                return $order;
            }
        }

        return false;
    }
}