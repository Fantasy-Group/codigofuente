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
class Webtex_FbaOrder_Block_Orders_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        $this->setId('wford_orders');
        $this->_controller = 'orders';
        $this->setUseAjax(true);

        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        /** @var Webtex_FbaOrder_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('wford/order')->getCollection();
        $collection->getSelect()->joinLeft(
            array('mOrder' => $collection->getTable('sales/order')),
            "main_table.mage_order_key = mOrder.entity_id",
            "mOrder.increment_id"
        );
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('wford')->__('ID'),
            'type' => 'number',
            'index' => 'entity_id',
        ));

        $this->addColumn('marketplace', array(
            'header' => Mage::helper('wfcom')->__('Marketplace'),
            'index' => 'marketplace_key',
            'type' => 'options',
            'options' => Mage::helper('wfcom')->getMarketplacesAsOptionArray(true)
        ));

        $this->addColumn('magento_order', array(
            'header' => Mage::helper('wford')->__('Magento Order'),
            'filter_index' => 'mOrder.increment_id',
            'index' => 'increment_id',
        ));

        $this->addColumn('seller_fulfillment_order_id', array(
            'header' => Mage::helper('wford')->__('Seller Fulfillment Order ID'),
            'index' => 'seller_fulfillment_order_id',
        ));

        $this->addColumn('internal_status', array(
            'header' => Mage::helper('wford')->__('Internal Status'),
            'index' => 'internal_status',
            'type' => 'options',
            'options' => Mage::helper('wford')->getInternalStatusAsOptionArray()

        ));

        $this->addColumn('fulfillment_order_status', array(
            'header' => Mage::helper('wford')->__('Fulfillment Order Status'),
            'index' => 'fulfillment_order_status',
            'type' => 'options',
            'options' => Mage::helper('wford')->getStatuses()
        ));

        $this->addColumn('shipping_speed_category', array(
            'header' => Mage::helper('wford')->__('Shipping Speed Category'),
            'index' => 'shipping_speed_category',
            'type' => 'options',
            'options' => Mage::helper('wford')->getCategories()
        ));

        $this->addColumn('displayable_order_date', array(
            'header' => Mage::helper('wford')->__('Displayable Order Date'),
            'index' => 'displayable_order_date',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('received_date', array(
            'header' => Mage::helper('wford')->__('Received Date'),
            'index' => 'received_date',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('status_updated_date', array(
            'header' => Mage::helper('wford')->__('Status Updated Date'),
            'index' => 'status_updated_date',
            'type' => 'datetime',
            'width' => '100px',
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
                        'caption' => Mage::helper('customer')->__('View'),
                        'url' => array('base' => '*/*/viewOrder'),
                        'field' => 'id'
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
        return $this->getUrl('*/*/ordersGrid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/viewOrder', array('id' => $row->getId()));
    }
}