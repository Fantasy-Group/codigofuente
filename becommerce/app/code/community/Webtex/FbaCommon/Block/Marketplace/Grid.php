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
class Webtex_FbaCommon_Block_Marketplace_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        $this->setId('fba_marketplace_grid');
        $this->_controller = 'adminhtml_marketplace';
        $this->setUseAjax(true);

        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        /** @var $collection Webtex_Fba_Model_Mws_Resource_Query_Collection */
        $collection = Mage::getModel('wfcom/marketplace')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => Mage::helper('wfcom')->__('ID'),
            'filter_index' => 'id',
            'index' => 'id',
            'renderer' => 'wfcom/marketplace_renderer_id'
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('wfcom')->__('Amazon Marketplace Status'),
            'filter_index' => 'status',
            'index' => 'status',
            'type' => 'options',
            'renderer' => 'wfcom/marketplace_renderer_status',
            'options' => $this->getCommonHelper()->getStatusOptions()
        ));

        $this->addColumn('access_key_id', array(
            'header' => Mage::helper('wfcom')->__('Access Key Id'),
            'filter_index' => 'access_key_id',
            'index' => 'access_key_id',
        ));

        $this->addColumn('secret_key', array(
            'header' => Mage::helper('wfcom')->__('Secret Key'),
            'filter_index' => 'secret_key',
            'index' => 'secret_key',
        ));

        $this->addColumn('merchant_id', array(
            'header' => Mage::helper('wfcom')->__('Merchant Id'),
            'filter_index' => 'merchant_id',
            'index' => 'merchant_id',
        ));

        $this->addColumn('amazon_marketplace', array(
            'header' => Mage::helper('wfcom')->__('Amazon Marketplace'),
            'align' => 'left',
            'filter_index' => 'amazon_marketplace',
            'index' => 'amazon_marketplace',
            'type' => 'options',
            'options' => Mage::getModel('wfcom/config_source_amazonMarketplace')->toArray(),
        ));

        $this->addColumn('editAction', array(
            'header' => Mage::helper('wfcom')->__('Edit'),
            'width' => '50px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('wfcom')->__('Edit'),
                    'url' => array('base' => '*/*/edit', 'params' => array('refresh' => 1)),
                    'field' => 'id'
                )
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'id',
        ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('marketplace');

        $this->getMassactionBlock()->addItem('inventory', array(
            'label' => Mage::helper('wfcom')->__('Sync Inventory'),
            'url' => $this->getUrl('*/*/massSyncInventory'),
        ));

        $this->getMassactionBlock()->addItem('orders', array(
            'label' => Mage::helper('wfcom')->__('Sync Orders'),
            'url' => $this->getUrl('*/*/massSyncOrders'),
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('wfcom')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('wfcom')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('disable', array(
            'label' => Mage::helper('wfcom')->__('Disable'),
            'url' => $this->getUrl('*/*/massDisable'),
        ));

        $this->getMassactionBlock()->addItem('enable', array(
            'label' => Mage::helper('wfcom')->__('Enable'),
            'url' => $this->getUrl('*/*/massEnable'),
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId(), 'refresh' => 1));
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getCommonHelper()
    {
        return Mage::helper('wfcom');

    }

}