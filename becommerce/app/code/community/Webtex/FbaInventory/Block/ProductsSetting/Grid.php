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
class Webtex_FbaInventory_Block_ProductsSetting_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        $this->setId('wfinv_product_settings');
        $this->_controller = 'adminhtml_inventory';
        $this->setUseAjax(true);

        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        /** @var $collection Webtex_FbaInventory_Model_GridCollection */
        $collection = Mage::getModel('wfinv/gridCollection');
        $collection->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id');
        $collection->getSelect()->group('e.entity_id');
        $collection->getSelect()->joinLeft(
            array('pLink' => $collection->getResource()->getTable('wfinv/link')),
            'pLink.mage_product_key = e.entity_id',
            'pLink.level_field as level_field'
        );

        $collection->getSelect()->joinLeft(
            array('pStock' => $collection->getResource()->getTable('wfinv/stock')),
            'pLink.stock_key=pStock.entity_id',
            'group_concat(' .
            'concat_ws( ' .
            '"\t",' .
            'marketplace_key, ' .
            'link_sku, ' .
            'total_qty, ' .
            'in_stock_qty,' .
            'level_field ' .
            ') ' .
            'separator "\n" ' .
            ') as marketplaces'
        );

        $collection->joinField(
            'sync_type',
            'wfinv/sync',
            'sync_type',
            'mage_product_key=entity_id',
            null,
            'left'
        );

        $collection->joinField(
            'marketplace_ids',
            'wfinv/sync',
            'marketplace_ids',
            'mage_product_key=entity_id',
            null,
            'left'
        );

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $collection->joinField(
                'qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'width' => '50px',
            'type' => 'number',
            'index' => 'entity_id',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('catalog')->__('Name'),
            'index' => 'name',
        ));

        $this->addColumn('type', array(
            'header' => Mage::helper('catalog')->__('Type'),
            'width' => '60px',
            'index' => 'type_id',
            'type' => 'options',
            'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));


        $this->addColumn('sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'width' => '80px',
            'index' => 'sku',
        ));


        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $this->addColumn('qty', array(
                'header' => Mage::helper('wfinv')->__('Local Qty'),
                'align' => 'right',
                'filter_index' => 'qty',
                'index' => 'qty'
            ));
        }


        $this->addColumn('sync_type', array(
            'header' => Mage::helper('wfinv')->__('Synchronization Type'),
            'index' => 'sync_type',
            'type' => 'options',
            'options' => Mage::helper('wfinv')->getSyncTypeOptionArray(),
        ));

        $this->addColumn('marketplace_ids', array(
            'header' => Mage::helper('wfinv')->__('Synchronization Marketplaces'),
            'filter' => false,
            'sortable' => false,
            'index' => 'marketplace_ids',
            'renderer' => 'wfinv/productsSetting_renderer_marketplaces',
        ));

        $this->addColumn('marketplaces', array(
            'header' => Mage::helper('wfinv')->__('Amazon Marketplaces'),
            'index' => 'marketplaces',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'wfinv/productsSetting_renderer_inventory',
        ));

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('wford')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('wfinv')->__('Edit'),
                        'url' => array('base' => '*/*/edit', 'params' => array('refresh' => 1)),
                        'field' => 'entity_id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            )
        );

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('entity_id' => $row->getEntityId(), 'refresh' => 1));
    }
}