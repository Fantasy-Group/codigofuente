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

/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$installer = $this;

/**
 * Creati
 */
$linkTable = $installer->getConnection()
    ->newTable($installer->getTable('wfinv/link'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Primary key')
    ->addColumn('mage_product_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Magento Product Entity Id')
    ->addColumn('stock_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Amazon Stock Key')
    ->addColumn('level_field', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable'  => false,
        'default' => 'None'
    ), 'Level Field')
    ->addIndex(
        $installer->getIdxName(
            'wfinv/link',
            array('mage_product_key', 'stock_key'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('mage_product_key', 'stock_key'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->setComment('Magento products linkage with Amazon stock');
$installer->getConnection()->createTable($linkTable);
/**
 * Create table 'wfinv/stock'
 */
$stockTable = $installer->getConnection()
    ->newTable($installer->getTable('wfinv/stock'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Primary key')
    ->addColumn('marketplace_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Amazon Marketplace ID')
    ->addColumn('link_sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, 64, array(
        'nullable'  => false,
        ), 'SKU In Marketplace')
    ->addColumn('total_qty', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Total Supply Quantity')
    ->addColumn('in_stock_qty', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'In Stock Supply Quantity')
    ->addColumn('blocked_qty', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Qty blocked by pending fulfillment orders')
    ->addColumn('is_sync_required', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Is sync required for this stock record')
    ->addIndex(
        $installer->getIdxName(
            'wfinv/stock',
            array('link_sku', 'marketplace_key'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('link_sku', 'marketplace_key'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->setComment('Amazon Stock Levels Table');
$installer->getConnection()->createTable($stockTable);
/**
 * Create table 'wfivn/sync'
 */
$configTable = $installer->getConnection()
    ->newTable($installer->getTable('wfinv/sync'))
    ->addColumn('mage_product_key', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Magento Product Entity Key')
    ->addColumn('sync_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
        'nullable'  => false,
        'default' => 'None'
        ), 'Sync Type')
    ->addColumn('marketplace_ids', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'default'   => '',
        ), 'Amazon Marketplace ID list for qty syncronization')
    ->setComment('Sync Inventory Config');
$installer->getConnection()->createTable($configTable);
$this->endSetup();
