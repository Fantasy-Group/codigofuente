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
class Webtex_FbaCommon_Block_Marketplace_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /** @var Varien_Data_Form */
    protected $form;

    protected $product;

    protected function _prepareForm()
    {
        $this->form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save'),
                'method' => 'post',
            )
        );

        $fieldset = $this->form->addFieldset(
            'marketplace',
            array(
                'legend' => Mage::helper('wfcom')->__('Marketplace')
            )
        );

        $fieldset->addField('id', 'hidden', array(
            'name' => 'id',
        ));

        $fieldset->addField('status', 'select', array(
            'name' => 'status',
            'label' => Mage::helper('wfcom')->__('Marketplace Status'),
            'required' => true,
            'options' => $this->getCommonHelper()->getStatusOptions(),
        ));

        $fieldset->addField('access_key_id', 'text', array(
            'name' => 'access_key_id',
            'label' => Mage::helper('wfcom')->__('Access Key Id'),
            'required' => true,
            'class' => 'validate-length minimum-length-20 maximum-length-20',
            'after_element_html' => '<small>alphanumeric sequence (20 character)</small>',
        ));

        $fieldset->addField('secret_key', 'text', array(
            'name' => 'secret_key',
            'label' => Mage::helper('wfcom')->__('Secret Key'),
            'required' => true,
            'class' => 'validate-length minimum-length-40 maximum-length-40',
            'after_element_html' => '<small>alphanumeric sequence (40 character)</small>',
        ));

        $fieldset->addField('merchant_id', 'text', array(
            'name' => 'merchant_id',
            'label' => Mage::helper('wfcom')->__('Merchant Id'),
            'required' => true,
            'after_element_html' => '<small>alphanumeric sequence </small>',
        ));

        $fieldset->addField('amazon_marketplace', 'select', array(
            'name' => 'amazon_marketplace',
            'label' => Mage::helper('wfcom')->__('Amazon Marketplace'),
            'required' => true,
            'options' => Mage::getModel('wfcom/config_source_amazonMarketplace')->toArray(),
        ));

        $this->form->setUseContainer(true);
        $this->form->addValues($this->prepareView());
        $this->setForm($this->form);

        return parent::_prepareForm();
    }

    /**
     * @return Varien_Object
     */
    private function prepareView()
    {
        $view = array();
        $marketplace = $this->getMarketplace();
        $savedForm = $this->getSavedForm();
        if ($savedForm
            && isset($savedForm['id'])
            && $marketplace
            && $marketplace->getId()
            && $marketplace->getId() == $savedForm['id']
        ) {
            $view = $savedForm;
        } elseif ($marketplace && $marketplace->getId()) {
            $view = $marketplace->getData();
        }

        return $view;
    }

    /**
     * @return Webtex_FbaCommon_Model_Marketplace
     */
    protected function getMarketplace()
    {
        return Mage::registry('amazon_marketplace');
    }

    /**
     * @return array
     */
    protected function getSavedForm()
    {
        return Mage::getSingleton('adminhtml/session')->getAmazonMarketplaceForm();
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getCommonHelper()
    {
        return Mage::helper('wfcom');
    }

}