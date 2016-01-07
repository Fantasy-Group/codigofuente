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
class Webtex_FbaOrder_Block_Orders_Create extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'orders';
        $this->_blockGroup = 'wford';
        $this->_mode = 'create';

        parent::__construct();
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->_addButton('parse', array(
            'label'     => Mage::helper('wford')->__('Parse'),
            'onclick'   => "$('submit-mode').setValue('parse'); $('edit_form').submit();"
        ), 1);
        $this->_addButton('preview', array(
            'label'     => Mage::helper('wford')->__('Preview'),
            'onclick'   => "$('submit-mode').setValue('preview'); $('edit_form').submit();"
        ), 1);
        $this->_addButton('save', array(
            'label'     => Mage::helper('adminhtml')->__('Save'),
            'onclick'   => "$('submit-mode').setValue('save'); editForm.submit();",
            'class'     => 'save',
        ), 1);
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('wford')->__(
            "Amazon fulfillment order creation form"
        );
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/orders');
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/saveOrder');
    }

}