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


/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$installer = $this;
/**
 * remove old attributes
 */
if ($installer->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'fba_marketplace_id') > 0) {
    $installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, 'fba_marketplace_id', 'source', null);
    $page = 0;
    while (true) {
        $page++;
        /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToSelect('amazon_sku');
        $productCollection->addAttributeToSelect('fba_marketplace_id');
        $productCollection->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
        $productCollection->setPage($page, 50);
        /** @var Mage_Catalog_Model_Product[] $productsToUpdate */
        $productsToUpdate = $productCollection->getItems();
        foreach ($productsToUpdate as $product) {
            /** @var Webtex_FbaCommon_Model_Marketplace $marketplace */
            $marketplace = Mage::helper('wfcom')->getMarketplace($product->getFbaMarketplaceId());
            if ($marketplace) {
                $sku = $product->getAmazonSku() ? $product->getAmazonSku() : $product->getSku();
                $index = $product->getFbaMarketplaceId() . $sku;
                /** @var Webtex_FbaInventory_Model_Product $stockHelper */
                $stockHelper = Mage::getModel('wfinv/product', $product);
                $stockHelper->link(
                    array($product->getFbaMarketplaceId()),
                    array(),
                    array($product->getFbaMarketplaceId() => $sku)
                );
                if ($marketplace->getInventoryMode() == 1) {
                    $stockHelper->setSync(
                        Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE,
                        array($marketplace->getId())
                    );
                }
                $stockHelper->save();
            }
        }
        if (count($productsToUpdate) < 50) {
            break;
        }
    }
    $installer->removeAttribute(Mage_Catalog_Model_Product::ENTITY, 'fba_marketplace_id');
}
if ($installer->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'amazon_sku') > 0) {
    $installer->removeAttribute(Mage_Catalog_Model_Product::ENTITY, 'amazon_sku');
}
$tablesToDelete = array(
    'fba_mws_tablerate',
    'fba_mws_stock',
    'fba_mws_inventory_config',
    'fba_mws_queries',
    'fba_mws_product',
    'fba_mws_tracking',
    'fba_mws_shipping'
);

foreach ($tablesToDelete as $table) {
    if ($installer->tableExists($table)) {
        $installer->getConnection()->dropTable($installer->getTable($table));
    }
}

/**
 * remove columns
 */
$columnsToDrop = array(
    'sales/shipment_track' => array(
        'amazon_track'
    ),
    'sales/order' => array(
        'is_fba',
        'fba_query_id',
        'fba_marketplace_id',
        'blocked_qty'
    ),
    'sales/shipment' => array(
        'amazon_shipment_id'
    ),
    'wfcom/marketplace' => array(
        'notification_emails',
        'notify_customers',
        'carrier_title',
        'send_order_immediately',
        'last_queue_execution_time',
        'next_queue_execution_time',
        'inventory_mode',
        'check_qty_before_place_order',
        'qty_check_field',
        'inventory_check_frequency',
        'check_orders',
        'shipping_currency',
        'ship_oos_as_non_fba'
    )
);
foreach ($columnsToDrop as $table => $columns) {
    if ($installer->tableExists($table)) {
        foreach ($columns as $column) {
            if ($installer->getConnection()->tableColumnExists($installer->getTable($table), $column)) {
                $installer->getConnection()->dropColumn($installer->getTable($table), $column);
            }
        }
    }
}
/**
 * Create table 'wfcom/marketplace'
 */
if (!$installer->tableExists('wfcom/marketplace')) {
    $marketplaceTable = $installer->getConnection()
        ->newTable($installer->getTable('wfcom/marketplace'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'ID')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable' => false,
            'default' => false,
        ), 'Marketplace Status')
        ->addColumn('access_key_id', Varien_Db_Ddl_Table::TYPE_TEXT, 40, array(
            'nullable' => false,
        ), 'Acess Key')
        ->addColumn('secret_key', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => false,
        ), 'Secret Key')
        ->addColumn('merchant_id', Varien_Db_Ddl_Table::TYPE_TEXT, 20, array(
            'nullable' => false,
        ), 'Merchant ID')
        ->addColumn('amazon_marketplace', Varien_Db_Ddl_Table::TYPE_SMALLINT, 2, array(
            'nullable' => false,
        ), 'Amazon Marketplace')
        ->addColumn('inventory_sync_last_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array())
        ->addColumn('orders_sync_last_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array())
        ->setComment('MWS Amazon Marketplace Table');
    $installer->getConnection()->createTable($marketplaceTable);
}
$this->endSetup();
