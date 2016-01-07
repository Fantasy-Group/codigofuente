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
 * @package    Webtex_Queue
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$installer = $this;
/**
 * Delete old table
 */

if ($installer->tableExists('fba_mws_queries')) {
    $installer->getConnection()->dropTable($installer->getTable('fba_mws_queries'));
}
/**
 * Create table 'wqueue/job'
 */
$jobTable = $installer->getConnection()
    ->newTable($installer->getTable('wqueue/job'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Primary key')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Job status')
    ->addColumn('priority', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Priority')
    ->addColumn('locked', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Locked')
    ->addColumn('tube', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
    ), 'Job tube')
    ->addColumn('job_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
    ), 'Job Type')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array('nullable' => false), 'Creation Time')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array('nullable' => false), 'Update Time')
    ->addColumn('job', Varien_Db_Ddl_Table::TYPE_BLOB, '16M', array(), 'Job serialized object')
    ->setComment('Webtex Queue Jobs');
$installer->getConnection()->createTable($jobTable);
$this->endSetup();
