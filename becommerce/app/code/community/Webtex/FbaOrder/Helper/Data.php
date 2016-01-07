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
class Webtex_FbaOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getStatuses()
    {
        return $this->getDistinctValues('wford/fulfillment_order', 'fulfillment_order_status');
    }

    public function getCategories()
    {
        return $this->getDistinctValues('wford/fulfillment_order', 'shipping_speed_category');
    }

    private function getDistinctValues($table, $column)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $table = $resource->getTableName($table);
        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = $resource->getConnection('core_read');
        $query = "SELECT distinct `{$column}` FROM `{$table}`";
        $result = $connection->fetchCol($query);

        return array_combine($result, $result);
    }

    public function getProductPoliciesAsOptionArray()
    {
        return array(
            Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ALL => 'All',
            Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_ANY => 'Any',
            Webtex_FbaOrder_Model_Autosend::PRODUCT_POLICY_WHOLE_ITEMS => 'Whole items'
        );
    }

    public function getStoresAsOptionArray()
    {
        $stores = array(
            '*' => 'Any Store'
        );
        foreach (Mage::app()->getStores() as $store) {
            /** @var Mage_Core_Model_Store $store */
            $stores[$store->getId()] = $store->getName();
        }

        return $stores;
    }

    public function getInternalStatusAsOptionArray()
    {
        return array(
            Webtex_FbaOrder_Model_Order::STATUS_PENDING => 'Pending',
            Webtex_FbaOrder_Model_Order::STATUS_IN_QUEUE => 'In Queue',
            Webtex_FbaOrder_Model_Order::STATUS_PLACED => 'Placed',
            Webtex_FbaOrder_Model_Order::STATUS_ERROR => 'Placing Error'
        );
    }

    public function getInternalStatusLabel($status)
    {
        $statuses = $this->getInternalStatusAsOptionArray();
        return $statuses[$status];
    }

    public function getShippingMethodsAsOptionArray()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        $shipping = array();
        foreach ($methods as $code => $carrier) {
            if ($methods = $carrier->getAllowedMethods()) {
                if (!$title = Mage::getStoreConfig("carriers/{$code}/title")) {
                    $title = $code;
                }
                foreach ($methods as $mcode => $method) {
                    $finalCode = $code . '_' . $mcode;
                    $shipping[$finalCode] = "{$title}, {$method} ({$finalCode})";
                }
            }
        }

        return $shipping;
    }

    public function getCountriesAsOptionArray()
    {
        $result = array(
            '*' => '*'
        );

        /** @var Mage_Directory_Model_Country[] $countryCollection */
        $countryCollection = Mage::getResourceModel('directory/country_collection');
        foreach ($countryCollection as $country) {
            $result[$country->getCountryId()] = $country->getName();
        }

        return $result;
    }

}