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
/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$installer = $this;

/**
 * Create table 'wford/autosend_rule'
 */
$rulesTable = $installer->getConnection()
    ->newTable($installer->getTable('wford/autosend_rule'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Primary key')
    ->addColumn('source_store_key', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable' => false,
        'default'   => '*',
    ), 'Source Order Store Keys')
    ->addColumn('source_shipping_method', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable'  => false,
        'default'   => '*',
        ), 'Source Order Shipping Method')
    ->addColumn('source_country_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 2, array(
        'nullable'  => false,
        'default'   => '*',
    ), 'Source Country ID')
    ->addColumn('source_zip_is_range', Varien_Db_Ddl_Table::TYPE_SMALLINT, 6, array(
        'unsigned' => true,
        'nullable'  => false,
    ), 'Zip is Range')
    ->addColumn('source_zip_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 64, array(
        'nullable'  => false,
        'default' => '*'
        ), 'Source zip code')
    ->addColumn('destination_shipping_speed', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Destination Shipping Speed')
    ->addColumn('destination_marketplace', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Marketplace Policy')
    ->addColumn('policy_product', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Product Policy')
    ->addColumn('sort_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Rules Sort Order')
    ->setComment('Amazon Fulfillment Rules');
$installer->getConnection()->createTable($rulesTable);
/**
 * Create table 'wford/fulfillment_order'
 * admin grid fields:
 * - entity_id
 * - seller_fulfillment_order_id
 * - marketplace_key | option array
 * - magento order
 * - displayable order id | new
 * - displayable order date time | new
 * - shipping speed category | new
 * - received date time | new
 * - fulfillment order status | dynamic option array | new
 * - status update date | new
 */
$orderTable = $installer->getConnection()
    ->newTable($installer->getTable('wford/fulfillment_order'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Primary key')
    ->addColumn('internal_status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Internal Status')
    ->addColumn('marketplace_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        'default' => 0,
    ), 'Marketplace Key')
    ->addColumn('mage_order_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
    ), 'Magento Order Key')
    ->addColumn('seller_fulfillment_order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 40, array(
        'nullable' => false
    ), 'MWS Fulfifllment order id')
    ->addColumn('displayable_order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(), 'Displayable Order ID')
    ->addColumn('displayable_order_date', Varien_Db_Ddl_Table::TYPE_DATETIME, 255, array(), 'Displayable Order date')
    ->addColumn('received_date', Varien_Db_Ddl_Table::TYPE_DATETIME, 255, array(), 'Received Date')
    ->addColumn('status_updated_date', Varien_Db_Ddl_Table::TYPE_DATETIME, 255, array(), 'Status Update Date')
    ->addColumn('shipping_speed_category', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(), 'Shipping Speed Category')
    ->addColumn('fulfillment_order_status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(), 'Shipping Speed Category')
    ->addColumn('request', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Serialized Request')
    ->addColumn('order', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Serialized order')
    ->addColumn('item', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Serialized Items')
    ->addColumn('tracks', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Tracking Array')
    ->addColumn('shipment', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Serialized Shipment')
    ->addColumn('shipment_map', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Shipment Map')
    ->addColumn('error_messages', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(), 'Error Messages')
    ->setComment('Amazon Fulfillment Orders');
$installer->getConnection()->createTable($orderTable);
$this->endSetup();
