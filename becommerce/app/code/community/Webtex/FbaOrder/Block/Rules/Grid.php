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
class Webtex_FbaOrder_Block_Rules_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        $this->setId('wford_rules');
        $this->_controller = 'rules';
        $this->setUseAjax(true);

        $this->setDefaultSort('sort_order');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        /** @var Webtex_FbaOrder_Model_Resource_Autosend_Collection $collection */
        $collection = Mage::getModel('wford/autosend')->getCollection();
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

        $this->addColumn('source_store_key', array(
            'header' => Mage::helper('wford')->__('Source Store'),
            'index' => 'source_store_key',
            'type' => 'options',
            'options' => Mage::helper('wford')->getStoresAsOptionArray()

        ));

        $this->addColumn('source_shipping_method', array(
            'header' => Mage::helper('wford')->__('Source Shipping'),
            'index' => 'source_shipping_method',
        ));

        $this->addColumn('source_country_id', array(
            'header' => Mage::helper('wford')->__('Source Country'),
            'index' => 'source_country_id',
            'type' => 'options',
            'options' => Mage::helper('wford')->getCountriesAsOptionArray()
        ));

        $this->addColumn('source_zip_code', array(
            'header' => Mage::helper('wford')->__('Source Zip'),
            'index' => 'source_zip_code',
        ));

        $this->addColumn('source_zip_code', array(
            'header' => Mage::helper('wford')->__('Source Zip'),
            'index' => 'source_zip_code',
        ));

        $this->addColumn('destination_shipping_speed', array(
            'header' => Mage::helper('wford')->__('Destination Shipping Speed'),
            'index' => 'destination_shipping_speed',
            'type' => 'options',
            'options' => Mage::helper('wfcom')->getSpeedAsOptionArray()
        ));

        $this->addColumn('destination_marketplace', array(
            'header' => Mage::helper('wfcom')->__('Destination Marketplace'),
            'index' => 'destination_marketplace',
            'type' => 'options',
            'options' => Mage::helper('wfcom')->getMarketplacesAsOptionArray(true)
        ));

        $this->addColumn('policy_product', array(
            'header' => Mage::helper('wford')->__('Product Policy'),
            'index' => 'policy_product',
            'type' => 'options',
            'options' => Mage::helper('wford')->getProductPoliciesAsOptionArray()
        ));

        $this->addColumn('sort_order', array(
            'header' => Mage::helper('wford')->__('Sort Order'),
            'index' => 'sort_order',
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
                        'caption' => Mage::helper('customer')->__('Edit'),
                        'url' => array('base' => '*/*/edit'),
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

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('rule');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('wfcom')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('wfcom')->__('Are you sure?')
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('entity_id' => $row->getId()));
    }
}