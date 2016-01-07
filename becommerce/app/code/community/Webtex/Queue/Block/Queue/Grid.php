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
class Webtex_Queue_Block_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected function _construct()
    {
        $this->setId('wqueu_order_grid');
        $this->_controller = 'adminhtml_webtexQueue';
        $this->setUseAjax(true);

        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
    }

    protected function _prepareCollection()
    {
        /** @var Webtex_Queue_Model_Resource_Job_Collection $collection */
        $collection = Mage::getModel('wqueue/job')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('wqueue')->__('ID'),
            'width' => '50px',
            'type' => 'number',
            'index' => 'entity_id',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('wqueue')->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => Mage::helper('wqueue')->getJobStatusAsOptionArray(),
        ));

        $this->addColumn('priority', array(
            'header' => Mage::helper('wqueue')->__('Priority'),
            'index' => 'priority',
        ));

        $this->addColumn('locked', array(
            'header' => Mage::helper('wqueue')->__('Locked'),
            'index' => 'locked',
        ));

        $this->addColumn('tube', array(
            'header' => Mage::helper('wqueue')->__('Tube'),
            'index' => 'tube',
        ));

        $this->addColumn('job_type', array(
            'header' => Mage::helper('wqueue')->__('Job Type'),
            'index' => 'job_type',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('wqueue')->__('Created At'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('updated_at', array(
            'header' => Mage::helper('wqueue')->__('Updated At'),
            'index' => 'updated_at',
            'type' => 'datetime',
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('job');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('wqueue')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('wqueue')->__('Are you sure?')
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}