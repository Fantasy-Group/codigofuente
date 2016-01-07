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
class Webtex_FbaOrder_Block_Rules_Rule_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /** @var Varien_Data_Form */
    protected $form;

    protected function _prepareForm()
    {
        $this->form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save'),
                'method' => 'post',
            )
        );
        $fieldSet = $this->form->addFieldset(
            'rule_form',
            array(
                'legend' => Mage::helper('wford')->__('Amazon Fulfillment Rule')
            )
        );

        $fieldSet->addField(
            'entity_id',
            'hidden',
            array(
                'name' => 'entity_id',
            )
        );

        $stores['*'] = '*';
        $stores += Mage::helper('wford')->getStoresAsOptionArray();
        $fieldSet->addField(
            'source_store_key',
            'select',
            array(
                'name' => 'source_store_key',
                'label' => Mage::helper('wford')->__('Source Store'),
                'values' => $stores
            )
        );

        $fieldSet->addField(
            'source_country_id',
            'select',
            array(
                'name' => 'source_country_id',
                'label' => Mage::helper('wford')->__('Source Country ID'),
                'values' => Mage::helper('wford')->getCountriesAsOptionArray()
            )
        );

        $methods['*'] = '*';
        $methods += Mage::helper('wford')->getShippingMethodsAsOptionArray();
        $methods['custom'] = 'Custom';

        $fieldSet->addField(
            'source_shipping_method',
            'select',
            array(
                'name' => 'source_shipping_method',
                'label' => Mage::helper('wford')->__('Source Shipping Method'),
                'values' => $methods
            )
        );


        $fieldSet->addField(
            'source_shipping_custom_method',
            'text',
            array(
                'name' => 'source_shipping_custom_method',
                'label' => Mage::helper('wford')->__('Custom Method Name'),
            )
        );

        $fieldSet->addField('source_zip_is_range', 'select', array(
            'name' => 'source_zip_is_range',
            'label' => Mage::helper('tax')->__('Zip/Post is Range'),
            'options' => array(
                '0' => Mage::helper('tax')->__('No'),
                '1' => Mage::helper('tax')->__('Yes'),
            )
        ));

        $fieldSet->addField('source_zip', 'text', array(
            'name' => 'source_zip',
            'label' => Mage::helper('tax')->__('Zip/Post Code'),
            'required' => true,
            'note' => Mage::helper('tax')->__("'*' - matches any; 'xyz*' - matches any that begins on 'xyz' and not longer than %d.",
                Mage::helper('tax')->getPostCodeSubStringLength()),
        ));

        $fieldSet->addField('source_zip_from', 'text', array(
            'name' => 'source_zip_from',
            'label' => Mage::helper('tax')->__('Range From'),
            'required' => true,
            'maxlength' => 9,
            'class' => 'validate-digits'
        ));

        $fieldSet->addField('source_zip_to', 'text', array(
            'name' => 'source_zip_to',
            'label' => Mage::helper('tax')->__('Range To'),
            'required' => true,
            'maxlength' => 9,
            'class' => 'validate-digits'
        ));

        $fieldSet->addField(
            'destination_shipping_speed',
            'select',
            array(
                'name' => 'destination_shipping_speed',
                'label' => Mage::helper('wford')->__('Destination Shipping Speed'),
                'values' => Mage::helper('wfcom')->getSpeedAsOptionArray()
            )
        );

        $fieldSet->addField(
            'destination_marketplace',
            'select',
            array(
                'name' => 'destination_marketplace',
                'label' => Mage::helper('wford')->__('Destination Marketplace'),
                'values' => Mage::helper('wfcom')->getMarketplacesAsOptionArray(true)
            )
        );

        $fieldSet->addField(
            'policy_product',
            'select',
            array(
                'name' => 'policy_product',
                'label' => Mage::helper('wford')->__('Product Policy'),
                'values' => Mage::helper('wford')->getProductPoliciesAsOptionArray()
            )
        );

        $fieldSet->addField('sort_order', 'text', array(
            'name' => 'sort_order',
            'label' => Mage::helper('salesrule')->__('Priority'),
        ));

        /** @var Mage_Adminhtml_Block_Widget_Form_Element_Dependence $dependencyBlock */
        $dependencyBlock = $this->getLayout()
            ->createBlock('adminhtml/widget_form_element_dependence');
        $dependencyBlock->addFieldMap('source_shipping_method', 'source_shipping_method');
        $dependencyBlock->addFieldMap('source_shipping_custom_method', 'source_shipping_custom_method');
        $dependencyBlock->addFieldDependence('source_shipping_custom_method', 'source_shipping_method', 'custom');
        $dependencyBlock->addFieldMap('source_zip_is_range', 'source_zip_is_range');
        $dependencyBlock->addFieldMap('source_zip', 'source_zip');
        $dependencyBlock->addFieldMap('source_zip_from', 'source_zip_from');
        $dependencyBlock->addFieldMap('source_zip_to', 'source_zip_to');
        $dependencyBlock->addFieldDependence('source_zip', 'source_zip_is_range', 0);
        $dependencyBlock->addFieldDependence('source_zip_from', 'source_zip_is_range', 1);
        $dependencyBlock->addFieldDependence('source_zip_to', 'source_zip_is_range', 1);
        $this->setChild('form_after', $dependencyBlock);


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
        $view = Mage::registry('fulfillment_rule');
        if (!$view) {
            $view = array(
                'sort_order' => 10
            );
        }


        return $view;
    }

}