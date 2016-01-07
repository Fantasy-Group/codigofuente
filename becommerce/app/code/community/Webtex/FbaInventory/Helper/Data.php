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

class Webtex_FbaInventory_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getSyncTypeOptionArray()
    {
       return array(
           Webtex_FbaInventory_Model_Product::TYPE_LOCAL => $this->__("Local Inventory"),
           Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE => $this->__("One Marketplace"),
           Webtex_FbaInventory_Model_Product::TYPE_AVG_IN_RANGE => $this->__("Average In Range"),
           Webtex_FbaInventory_Model_Product::TYPE_MAX_IN_RANGE => $this->__("Max In Range"),
           Webtex_FbaInventory_Model_Product::TYPE_MIN_IN_RANGE => $this->__("Min In Range"),
       );
    }

    public function getLevelFieldAsOptionArray()
    {
        return array(
            Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY => $this->__('In Stock Qty'),
            Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY => $this->__('Total Qty')
        );
    }

    public function getDefaultLevelField()
    {
        return Webtex_FbaInventory_Model_Product::FIELD_DEFAULT;
    }

    public function addProductsToReindex($products)
    {
        $alreadyRegistered = Mage::registry('fba_sync_products');
        if (!$alreadyRegistered) {
            $alreadyRegistered = array();
        }
        Mage::unregister("fba_sync_products");
        Mage::register("fba_sync_products", array_merge($alreadyRegistered, $products));
    }

    public function reindexProductsIfNeeded()
    {
        $updatedProductIds = Mage::registry('fba_sync_products');
        if ($updatedProductIds !== null
            && count($updatedProductIds)
        ) {
            Mage::getSingleton('index/indexer')->indexEvents(
                Mage_CatalogInventory_Model_Stock_Item::ENTITY,
                Mage_Index_Model_Event::TYPE_SAVE
            );

            Mage::dispatchEvent('catalog_product_stock_item_mass_change', array(
                'products' => $updatedProductIds,
            ));
        }
    }
}
