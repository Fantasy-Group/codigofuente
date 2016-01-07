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
class Webtex_FbaOrder_Block_Orders_Create_Form extends Webtex_FbaCommon_Block_FormWithGrid
{
    protected $values;

    protected function _prepareForm()
    {
        $this->form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/saveOrder'),
                'method' => 'post',
            )
        );
        $this->values = $this->getValues();

        $this->addBasicFS();
        $this->addOrderInfoFS();
        $this->addAddressInfoFS();
        $this->addProductsInfoFS();


        $this->form->setUseContainer(true);
        $this->form->setValues($this->values);
        $this->setForm($this->form);

        return Mage_Adminhtml_Block_Widget_Form::_prepareForm();
    }

    private function addBasicFS()
    {
        $fieldSet = $this->form->addFieldset(
            'order_main',
            array(
                'legend' => Mage::helper('wford')->__('Basic')
            )
        );

        $fieldSet->addField(
            'submit-mode',
            'hidden',
            array(
                'name' => 'submit-mode',
                'required' => true
            )
        );

        $fieldSet->addField(
            'marketplace',
            'select',
            array(
                'name' => 'marketplace',
                'label' => Mage::helper('wford')->__('Amazon Marketplace'),
                'options' => $this->getCommonHelper()->getMarketplacesAsOptionArray(true),
                'required' => 'true'
            )
        );

        $fieldSet->addField(
            'mage_order',
            'text',
            array(
                'name' => 'mage_order',
                'label' => Mage::helper('wford')->__('Magento Order'),
                'after_element_html' => '<br /><small><b>Optional</b>. Input magento order increment ID and push' .
                    ' <b>Parse</b> to <br/> generate fulfillment order template based on marketplace ' .
                    'and this order.</small>',
            )
        );
    }

    private function addOrderInfoFS()
    {
        $fieldSet = $this->form->addFieldset(
            'order_info',
            array(
                'legend' => Mage::helper('wford')->__('Order Info')
            )
        );

        $fieldSet->addField(
            'displayable_order_id',
            'text',
            array(
                'name' => 'displayable_order_id',
                'label' => Mage::helper('wford')->__('Displayable Order ID'),
                'after_element_html' => '<br/><small>A fulfillment order identifier that you create. This value' .
                    '<br/>displays as the order identifier in recipient-facing ' .
                    '<br/>materials such as the outbound shipment packing slip.' .
                    '<br/>The value of <b>DisplayableOrderId</b> should match the order' .
                    '<br/>identifier that you provide to your customer.</small>',
                'required' => true
            )
        );

        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldSet->addField(
            'displayable_order_date_time',
            'date',
            array(
                'name' => 'displayable_order_date_time',
                'label' => Mage::helper('wford')->__('Displayable Order Date'),
                'required' => true,
                'image' => $this->getSkinUrl('images/grid-cal.gif'),
                'format' => $dateFormatIso,
                'time' => true
            )
        );

        $fieldSet->addField(
            'displayable_order_comment',
            'text',
            array(
                'name' => 'displayable_order_comment',
                'label' => Mage::helper('wford')->__('Displayable Order Comment'),
                'after_element_html' => '<br/><small>Order-specific text that appears in ' .
                    'customer-facing <br/>materials such as the outbound shipment packing slip.</small>',
                'required' => true
            )
        );

        $fieldSet->addField(
            'shipping_speed_category',
            'select',
            array(
                'name' => 'shipping_speed_category',
                'label' => Mage::helper('wford')->__('Shipping Speed Category'),
                'options' => $this->getCommonHelper()->getSpeedAsOptionArray(),
                'required' => true
            )
        );

        $fieldSet->addField(
            'notification_email_list',
            'text',
            array(
                'name' => 'notification_email_list',
                'label' => Mage::helper('wford')->__('Notification Email List'),
                'after_element_html' => '<br/><small>A comma-separated list of email addresses that are used' .
                    '<br/>by Amazon to send ship-complete notifications <br/>to your customers on your behalf.</small>',
            )
        );
    }

    private function addAddressInfoFS()
    {
        $fieldSet = $this->form->addFieldset(
            'order_address',
            array(
                'legend' => Mage::helper('wford')->__('Shipping Address')
            )
        );

        $fieldSet->addField(
            'name',
            'text',
            array(
                'name' => 'name',
                'label' => Mage::helper('wford')->__('Name'),
                'maxlength' => 50,
                'required' => true
            )
        );

        $fieldSet->addField(
            'street',
            'multiline',
            array(
                'name' => 'street',
                'maxlength' => 60,
                'label' => Mage::helper('wford')->__('Street'),
                'required' => true
            )
        )->setLineCount(3);

        $fieldSet->addField('city', 'text', array(
            'name' => 'city',
            'maxlength' => 50,
            'label' => Mage::helper('wford')->__('City'),
            'required' => 'true'
        ));

        $countries = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
        unset($countries[0]);

        $regionCollection = Mage::getModel('directory/region')
            ->getCollection()
            ->addCountryFilter($this->values['country_code']);

        $regions = $regionCollection->toOptionArray();
        $regionField = array(
            'name' => 'region_code',
            'label' => Mage::helper('tax')->__('State'),
        );
        if ($regions) {
            $regionField['values'] = $regions;
            $regionText = "<input id='region' style='display: none' name='region' value='{$this->values['region']}' " .
                "maxlength='150' class=' input-text' type='text'>";
        } else {
            $regionText = "<input id='region' name='region' value='{$this->values['region']}' " .
                "maxlength='150' class=' input-text' type='text'>";
        }
        $regionField['after_element_html'] =
            $regionText .
            '<script type="text/javascript">' .
            "var updater = new RegionUpdater('country_code', 'region', 'region_code', " .
            $this->helper('directory')->getRegionJson() . ");" .
            "</script>";


        $fieldSet->addField('country_code', 'select', array(
            'name' => 'country_code',
            'label' => Mage::helper('tax')->__('Country'),
            'required' => true,
            'values' => $countries
        ));


        $fieldSet->addField('region_code', 'select', $regionField);

        $fieldSet->addField('postcode', 'text', array(
            'name' => 'postcode',
            'maxlength' => 20,
            'label' => Mage::helper('wford')->__('Zip/Postal Code'),
        ));

        $fieldSet->addField('telephone', 'text', array(
            'name' => 'telephone',
            'maxlength' => 20,
            'label' => Mage::helper('wford')->__('Telephone'),
        ));
    }

    private function addProductsInfoFS()
    {
        $fieldSet = $this->form->addFieldset(
            'order_items_grid',
            array(
                'legend' => Mage::helper('wford')->__('Order Items')
            )
        );

        $this->addGridField(
            $fieldSet,
            'order_items',
            array(
                'name' => 'order_items',
                'label' => Mage::helper('wford')->__('Order Items'),
            )
        );
    }

    private function getOrderItems($rawData)
    {
        $readOnly = array(
            'amazon_qty'
        );

        $result = array();
        foreach ($rawData as $item) {
            $collectionItem = array();
            foreach ($item as $key => $value) {
                if (in_array($key, $readOnly)) {
                    $collectionItem[$key] = $this->getElement(
                        'note',
                        array(
                            'text' => $value
                        )
                    );
                } else {
                    $collectionItem[$key] = $this->getTextElement($value);
                }
            }
            $result['collection'][] = $collectionItem;
        }
        $result['columns'] = array(
            'seller_sku' => 'Seller Sku',
            'qty' => 'Qty to Fulfill',
            'amazon_qty' => 'Amazon Qty'
        );
        $result['template'] = array(
            'seller_sku' => $this->getTextElement(''),
            'qty' => $this->getTextElement(0),
            'amazon_qty' => $this->getElement('note', array())->setReadonly(true)
        );

        return $result;
    }

    private function getValues()
    {
        $result = Mage::getSingleton('core/session')->getFbaOrder();
        if (!$result) {
            $result = array();
        }
        if (!isset($result['order_items'])) {
            $result['order_items'] = array();
        }
        $result['order_items'] = $this->getOrderItems($result['order_items']);

        return array_merge($this->getDefaultValues(), $result);
    }


    private function getDefaultValues()
    {
        $result = array();
        $result['displayable_order_date_time'] = Mage::helper('core')
            ->formatDate(
                Mage::getModel('core/date')->date(),
                Mage_Core_Model_Locale::FORMAT_TYPE_SHORT,
                true
            );
        $result['country_code'] = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_COUNTRY);
        $result['region_code'] = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_DEFAULT_REGION);
        $result['region'] = "";

        return $result;

    }

}
