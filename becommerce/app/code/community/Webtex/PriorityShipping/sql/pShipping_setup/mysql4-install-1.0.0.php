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
 * @package    Webtex_PriorityShipping
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$installer = $this;

/**
 * Create table 'shipping/tablerate'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('pShipping/tablerate'))
    ->addColumn('pk', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Primary key')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'default' => '0',
    ), 'Website Id')
    ->addColumn('dest_country_id', Varien_Db_Ddl_Table::TYPE_TEXT, 4, array(
        'nullable' => false,
        'default' => '0',
    ), 'Destination coutry ISO/2 or ISO/3 code')
    ->addColumn('dest_region_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'default' => '0',
    ), 'Destination Region Id')
    ->addColumn('dest_zip', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable' => false,
        'default' => '*',
    ), 'Destination Post Code (Zip)')
    ->addColumn('method_code', Varien_Db_Ddl_Table::TYPE_TEXT, 10, array(
        'nullable' => false,
    ), 'Shipping Method Code')
    ->addColumn('condition_name', Varien_Db_Ddl_Table::TYPE_TEXT, 20, array(
        'nullable' => false,
    ), 'Rate Condition name')
    ->addColumn('condition_value', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable' => false,
        'default' => '0.0000',
    ), 'Rate condition value')
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable' => false,
        'default' => '0.0000',
    ), 'Price')
    ->addColumn('cost', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        'nullable' => false,
        'default' => '0.0000',
    ), 'Cost')
    ->addIndex(
        $installer->getIdxName(
            'shipping/tablerate',
            array(
                'website_id',
                'dest_country_id',
                'dest_region_id',
                'dest_zip',
                'method_code',
                'condition_name',
                'condition_value'
            ),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array(
            'website_id',
            'dest_country_id',
            'dest_region_id',
            'dest_zip',
            'method_code',
            'condition_name',
            'condition_value'
        ),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
    ->setComment('Shipping Tablerate');
$installer->getConnection()->createTable($table);
$this->endSetup();
