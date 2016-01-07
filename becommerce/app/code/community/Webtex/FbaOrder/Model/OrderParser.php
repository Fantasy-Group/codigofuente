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
class Webtex_FbaOrder_Model_OrderParser
{
    /** @var  Mage_Sales_Model_Order */
    protected $order;

    /** @var  array */
    protected $extract;

    /** @var  Webtex_FbaInventory_Model_Product[] */
    protected $aStock;

    /** @var  Webtex_FbaOrder_Model_Autosend[] */
    protected $matchingRules;

    function __construct(Mage_Sales_Model_Order $order)
    {
        $this->order = $order;
        $this->extractItems();
        $this->initAutosendRules();
    }

    protected function extractItems()
    {
        $extract = array();
        $productIds = array();
        if ($this->order->canShip()) {
            /** @var Mage_Sales_Model_Order_Item[] $items */
            $items = $this->order->getAllItems();

            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $productIds[$item->getProductId()] = 1;
                $extract[$item->getId()] = array(
                    'product_id' => $item->getProductId(),
                    'mage_sku' => $item->getSku(),
                    'qty_to_ship' => $this->getQtyToShip($item),
                    'item' => $item,
                );
                foreach ($item->getChildrenItems() as $child) {
                    /** @var Mage_Sales_Model_Order_Item $child */
                    $productIds[$child->getProductId()] = 1;
                    $extract[$item->getId()]['children'][$child->getId()] = array(
                        'product_id' => $child->getProductId(),
                        'mage_sku' => $child->getSku(),
                        'qty_to_ship' => $this->getQtyToShip($child),
                        'item' => $child,
                    );
                }
            }
        }
        /** @var Webtex_FbaInventory_Model_ProductMass $productMass */
        $productMass = Mage::getModel('wfinv/productMass');
        $this->aStock = $productMass->getCollection(array_keys($productIds));
        $this->extract = $extract;
    }

    protected function initAutosendRules()
    {
        $this->matchingRules = array();
        if ($this->order->canShip()) {
            /** @var Webtex_FbaOrder_Model_Resource_Autosend_Collection $rulesCollection */
            $rulesCollection = Mage::getModel('wford/autosend')->getCollection();

            $zipTemplates = $this->calculateZipTemplates($this->order->getShippingAddress()->getPostcode());
            $zipTemplates[] = '*';

            $rulesCollection->getSelect()->where(
                "(source_zip_is_range = 1 AND :zip BETWEEN SUBSTRING_INDEX(source_zip_code, '-', 1) " .
                "AND SUBSTRING_INDEX(source_zip_code, '-', -1))" .
                " OR (source_zip_is_range = 0 and source_zip_code IN (?))",
                $zipTemplates
            );

            $rulesCollection->addOrder('sort_order');
            $rulesCollection->addFieldToFilter(
                'source_store_key',
                array('in' => array('*', $this->order->getStoreId()))
            )->addFieldToFilter(
                'source_shipping_method',
                array('in' => array('*', $this->order->getShippingMethod()))
            )->addFieldToFilter(
                'source_country_id',
                array('in' => array('*', $this->order->getShippingAddress()->getCountryId()))
            )->addBindParam('zip', $this->order->getShippingAddress()->getPostcode())
                ->setOrder('sort_order')
                ->addOrder('entity_id', Varien_Data_Collection_Db::SORT_ORDER_ASC);
            $rulesCollection->join(
                array('mPlace' => 'wfcom/marketplace'),
                'main_table.destination_marketplace = mPlace.id',
                'status as marketplace_status'
            );
            $rulesCollection->addFieldToFilter(
                'mPlace.status',
                1
            );
            $this->matchingRules = $rulesCollection->getItems();
        }
    }

    /**
     * @return bool|Webtex_FbaOrder_Model_Order
     */
    public function getOrderRequests()
    {
        foreach ($this->matchingRules as $rule) {
            $toShip = $this->parseWithRule($rule);
            if ($toShip !== false) {
                /** @var Webtex_FbaOrder_Model_Order $orderLog */
                $orderLog = Mage::getModel('wford/order');
                $orderLog->setMageOrderKey($this->order->getId());
                $orderLog->setMarketplaceKey($rule->getDestinationMarketplace());
                $orderLog->save();
                $fulfillmentOrder = $this->getConverter()->getFulfillmentOrder(
                    $this->order,
                    $orderLog->getId()
                );
                $marketplace = $this->getComHelper()->getMarketplace($rule->getDestinationMarketplace());
                $fulfillmentOrder->setSellerId($marketplace->getMerchantId());
                $fulfillmentOrder->setDestinationAddress(
                    $this->getConverter()->getShippingAddress($this->order->getShippingAddress())
                );
                $fulfillmentOrder->setItems($this->getConverter()->getItemList($toShip));
                $fulfillmentOrder->setNotificationEmailList(
                    $this->getConverter()->getNotificationEmails(explode(',', $marketplace->getNotificationEmails()))
                );
                $fulfillmentOrder->setSellerFulfillmentOrderId(
                    $this->order->getIncrementId() . '_' . $orderLog->getEntityId()
                );
                $fulfillmentOrder->setShippingSpeedCategory($rule->getDestinationShippingSpeed());
                $orderLog->setSellerFulfillmentOrderId($fulfillmentOrder->getSellerFulfillmentOrderId());
                $orderLog->setShippingSpeedCategory($rule->getDestinationShippingSpeed());
                $orderLog->setRequest($fulfillmentOrder);
                $orderLog->save();

                return $orderLog;
            }

        }

        return false;
    }

    public function generateShipment(FBAOutboundServiceMWS_Model_FulfillmentShipment $amazonShipment, $marketplaceId)
    {
        $productsToShip = $this->extract;
        if ($this->order->canShip()
            && strtolower($amazonShipment->getFulfillmentShipmentStatus()) == 'shipped'
            && $amazonShipment->isSetFulfillmentShipmentItem()
        ) {
            $amazonShipmentItems = array();
            /** @var FBAOutboundServiceMWS_Model_FulfillmentShipmentItem[] $aItemList */
            $aItemList = $amazonShipment->getFulfillmentShipmentItem()->getmember();
            foreach ($aItemList as $aItem) {
                $amazonShipmentItems[$aItem->getSellerFulfillmentOrderItemId()] = $aItem->getQuantity();
            }

            /** @var $convertOrder Mage_Sales_Model_Convert_Order */
            $convertOrder = Mage::getModel('sales/convert_order');
            $shipment = $convertOrder->toShipment($this->order);

            foreach ($productsToShip as $product) {
                $linkSku = $this->aStock[$product['product_id']]->getLinkSku($marketplaceId);
                /** @var Mage_Sales_Model_Order_Item $item */
                $item = $product['item'];
                if ($linkSku
                    && isset($amazonShipmentItems[$item->getId()])
                    && $amazonShipmentItems[$item->getId()] > 0
                ) {
                    $sItem = $convertOrder->itemToShipmentItem($item);
                    $sItem->setQty($amazonShipmentItems[$item->getId()]);
                    $shipment->addItem($sItem);
                } elseif (isset($product['children'])) {
                    foreach ($product['children'] as $child) {
                        $linkSku = $this->aStock[$child['product_id']]->getLinkSku($marketplaceId);
                        /** @var Mage_Sales_Model_Order_Item $childItem */
                        $childItem = $child['item'];
                        if ($linkSku
                            && isset($amazonShipmentItems[$childItem->getId()])
                            && $amazonShipmentItems[$childItem->getId()] > 0
                        ) {
                            $item = $child['item'];
                            if ($item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                                /** @var Mage_Sales_Model_Order_Item $childItem */
                                $item = $item->getParentItem();
                            }
                            $sItem = $convertOrder->itemToShipmentItem($item);
                            $sItem->setQty($amazonShipmentItems[$childItem->getId()]);
                            $shipment->addItem($sItem);
                        }
                    }
                }
            }

            if (count($shipment->getAllItems())) {
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                return $shipment;
            }
        }

        return false;
    }

    public function parseForMarketplace($marketplace)
    {
        $productsToFulfill = $this->extract;
        $result = array();
        foreach ($productsToFulfill as $itemId => $product) {
            $stock = $this->aStock[$product['product_id']];
            $level = $stock->getStockLevel($marketplace->getId());
            $sellerSku = $stock->getLinkSku($marketplace->getId());
            if ($product['qty_to_ship']
                && $sellerSku
            ) {
                $result[] = array(
                    'qty' => $product['qty_to_ship'],
                    'amazon_qty' => $level,
                    'seller_sku' => $sellerSku,
                    'mage_sku' => $product['mage_sku'],
                    'item_id' => $itemId
                );
            } elseif (isset($product['children'])
                && count($product['children'])
            ) {
                foreach ($product['children'] as $childItemId => $child) {
                    $childStock = $this->aStock[$child['product_id']];
                    $childLevel = $childStock->getStockLevel($marketplace->getId());
                    $childSellerSku = $childStock->getLinkSku($marketplace->getId());
                    if ($child['qty_to_ship']
                        && $childSellerSku
                    ) {
                        $result[] = array(
                            'qty' => $child['qty_to_ship'],
                            'amazon_qty' => $childLevel,
                            'seller_sku' => $childSellerSku,
                            'mage_sku' => $child['mage_sku'],
                            'item_id' => $childItemId
                        );
                    }
                }
            }
        }

        if (!count($result)) {
            return false;
        }

        return $result;
    }

    protected function parseWithRule(Webtex_FbaOrder_Model_Autosend $rule)
    {
        $productsToFulfill = $this->extract;
        $result = array();
        $marketplace = $this->getComHelper()->getMarketplace($rule->getDestinationMarketplace());
        if ($marketplace) {
            foreach ($productsToFulfill as $itemId => $product) {
                $stock = $this->aStock[$product['product_id']];
                $level = $stock->getStockLevel($marketplace->getId());
                if ($product['qty_to_ship']
                    && $level
                    && $level >= $product['qty_to_ship']
                ) {
                    $result[] = $this->getConverter()->getItem(
                        $stock->getLinkSku($marketplace->getId()),
                        $product['qty_to_ship'],
                        $itemId
                    );
                } elseif ($product['qty_to_ship']
                    && $level
                    && $level < $product['qty_to_ship']
                    && $rule->getPolicyProduct() == Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ANY
                ) {
                    $result[] = $this->getConverter()->getItem(
                        $stock->getLinkSku($marketplace->getId()),
                        $level,
                        $itemId
                    );
                } elseif (isset($product['children'])
                    && count($product['children'])
                ) {
                    foreach ($product['children'] as $childItemId => $child) {
                        $childStock = $this->aStock[$child['product_id']];
                        $childLevel = $childStock->getStockLevel($marketplace->getId());
                        if ($child['qty_to_ship']
                            && $childLevel
                            && $childLevel >= $child['qty_to_ship']
                        ) {
                            $result[] = $this->getConverter()->getItem(
                                $childStock->getLinkSku($marketplace->getId()),
                                $child['qty_to_ship'],
                                $childItemId
                            );
                        } elseif (
                            $child['qty_to_ship']
                            && $childLevel
                            && $childLevel < $child['qty_to_ship']
                            && $rule->getPolicyProduct() == Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ANY
                        ) {
                            $result[] = $this->getConverter()->getItem(
                                $childStock->getLinkSku($marketplace->getId()),
                                $childLevel,
                                $childItemId
                            );
                        } elseif ($rule->getPolicyProduct() == Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ALL) {
                            return false;
                        }
                    }
                } elseif ($rule->getPolicyProduct() == Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ALL) {
                    return false;
                }
            }
        }

        if (!count($result)) {
            return false;
        }

        return $result;
    }

    private function calculateZipTemplates($zip)
    {
        $length = strlen($zip);
        $templates = array();
        $templates[] = str_repeat('*', $length);
        $prefix = '';
        $prefixLen = 0;
        foreach (str_split($zip) as $zipDigit) {
            $prefix .= $zipDigit;
            $prefixLen += 1;
            $templates[] = $prefix . str_repeat('*', $length - $prefixLen);
        }

        return $templates;
    }

    private function getQtyToShip(Mage_Sales_Model_Order_Item $item)
    {
        if (!$item->getIsVirtual() && !$item->getLockedDoShip()) {
            if ($item->getParentItem()
                && $item->getParentItem()->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
                && $item->getQtyToShip() === 0
            ) {
                return $item->getParentItem()->getQtyToShip();
            } else {
                return $item->getQtyToShip();
            }
        }

        return 0;
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getComHelper()
    {
        return Mage::helper('wfcom');
    }

    /**
     * @return Webtex_FbaOrder_Model_Converter
     */
    protected function getConverter()
    {
        return Mage::getModel('wford/converter');
    }

}