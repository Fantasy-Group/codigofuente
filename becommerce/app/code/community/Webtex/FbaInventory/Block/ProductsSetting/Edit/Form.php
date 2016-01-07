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

class Webtex_FbaInventory_Block_ProductsSetting_Edit_Form extends Webtex_FbaCommon_Block_FormWithGrid
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

        $info = $this->form->addFieldset(
            'info',
            array(
                'legend' => Mage::helper('wfinv')->__('Product Info')
            )
        );

        $info->addField(
            'entity_id',
            'hidden',
            array(
                'name' => 'entity_id',
            )
        );

        $info->addField(
            'product_sku',
            'label',
            array(
                'name' => 'product_sku',
                'label' => Mage::helper('wfinv')->__('Magento Product SKU')
            )
        );

        $info->addField(
            'product_name',
            'label',
            array(
                'name' => 'product_name',
                'label' => Mage::helper('wfinv')->__('Magento Product Name')
            )
        );

        $info->addField(
            'product_type',
            'label',
            array(
                'name' => 'product_type',
                'label' => Mage::helper('wfinv')->__('Magento Product Type')
            )
        );

        $info->addField(
            'product_qty',
            'label',
            array(
                'name' => 'product_qty',
                'label' => Mage::helper('wfinv')->__('Magento Product Qty')
            )
        );

        $links = $this->form->addFieldset(
            'links',
            array(
                'legend' => Mage::helper('wfinv')->__('Links with amazon marketplaces')
            )
        );

        $this->addGridField(
            $links,
            'links_grid',
            array(
                'name' => 'links_grid',
                'label' => Mage::helper('wfinv')->__("Product Links")
            )
        );

        $sync = $this->form->addFieldset(
            'sync',
            array(
                'legend' => Mage::helper('wfinv')->__('Product Synchronization Settings')
            )
        );

        $sync->addField(
            'sync_type',
            'select',
            array(
                'name' => 'sync_type',
                'label' => 'Synchronization type',
                'values' => Mage::helper('wfinv')->getSyncTypeOptionArray()
            )
        );


        $sync->addField(
            'marketplace_avg',
            'multiselect',
            array(
                'name' => 'marketplace_avg',
                'label' => Mage::helper('wford')->__('Sync Marketplaces'),
                'values' => $this->getCommonHelper()->convertOptions(
                    $this->getCommonHelper()->getMarketplacesAsOptionArray(true)
                ),
                'required' => true,
                'after_element_html' => '<br/>Can include only marketplaces which are linked with product'
            )
        );

        $sync->addField(
            'marketplace_max',
            'multiselect',
            array(
                'name' => 'marketplace_max',
                'label' => Mage::helper('wford')->__('Sync Marketplaces'),
                'values' => $this->getCommonHelper()->convertOptions(
                    $this->getCommonHelper()->getMarketplacesAsOptionArray(true)
                ),
                'required' => true,
                'after_element_html' => '<br/>Can include only marketplaces which are linked with product'
            )
        );

        $sync->addField(
            'marketplace_min',
            'multiselect',
            array(
                'name' => 'marketplace_min',
                'label' => Mage::helper('wford')->__('Sync Marketplaces'),
                'values' => $this->getCommonHelper()->convertOptions(
                    $this->getCommonHelper()->getMarketplacesAsOptionArray(true)
                ),
                'required' => true,
                'after_element_html' => '<br/>Can include only marketplaces which are linked with product'
            )
        );

        $sync->addField(
            'marketplace',
            'select',
            array(
                'name' => 'marketplace',
                'label' => Mage::helper('wford')->__('Sync Marketplaces'),
                'values' => $this->getCommonHelper()->getMarketplacesAsOptionArray(true),
                'required' => true,
                'after_element_html' => '<br/>Can include only marketplace which is linked with product'
            )
        );
        /** @var Mage_Adminhtml_Block_Widget_Form_Element_Dependence $dependencyBlock */
        $dependencyBlock = $this->getLayout()
            ->createBlock('adminhtml/widget_form_element_dependence');
        $dependencyBlock->addFieldMap('sync_type', 'sync_type')
            ->addFieldMap('marketplace_min', 'marketplace_min')
            ->addFieldMap('marketplace_max', 'marketplace_max')
            ->addFieldMap('marketplace_avg', 'marketplace_avg')
            ->addFieldMap('marketplace', 'marketplace')
            ->addFieldDependence('marketplace', 'sync_type', Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE)
            ->addFieldDependence('marketplace_avg', 'sync_type', Webtex_FbaInventory_Model_Product::TYPE_AVG_IN_RANGE)
            ->addFieldDependence('marketplace_min', 'sync_type', Webtex_FbaInventory_Model_Product::TYPE_MIN_IN_RANGE)
            ->addFieldDependence('marketplace_max', 'sync_type', Webtex_FbaInventory_Model_Product::TYPE_MAX_IN_RANGE);
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

        $view = array();

        /** product info */
        $view['entity_id'] = $this->getMageProduct()->getEntityId();
        $savedForm = $this->getSavedForm();
        $view['product_sku'] = $this->getMageProduct()->getSku();
        $view['product_name'] = $this->getMageProduct()->getName();
        /** @var Mage_Catalog_Model_Product_Type $type */
        $type = Mage::getSingleton('catalog/product_type');
        $typeLabels = $type->getOptionArray();
        $view['product_type'] = $typeLabels[$this->getMageProduct()->getTypeId()];
        $view['product_qty'] = $this->getMageProduct()->getStockItem()->getQty();
        if ($savedForm && $savedForm['entity_id'] == $view['entity_id']) {
            /** sync */
            $view['sync_type'] = $savedForm['sync_type'];
            if ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE) {
                $view['marketplace'] = $savedForm['marketplace'];
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MAX_IN_RANGE) {
                $view['marketplace_max'] = $savedForm['marketplace_max'];
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MIN_IN_RANGE) {
                $view['marketplace_min'] = $savedForm['marketplace_min'];
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_AVG_IN_RANGE) {
                $view['marketplace_avg'] = $savedForm['marketplace_avg'];
            }
            $view['links_grid'] = $this->prepareLinks($savedForm['links_grid']);
        } else {

            /** links */
            $view['links_grid'] = $this->prepareLinks();

            /** sync */
            $view['sync_type'] = $this->getFProduct()->getSyncType();
            if ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE) {
                $view['marketplace'] = $this->getFProduct()->getSyncMarketplace();
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MAX_IN_RANGE) {
                $view['marketplace_max'] = $this->getFProduct()->getSyncMarketplace();
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MIN_IN_RANGE) {
                $view['marketplace_min'] = $this->getFProduct()->getSyncMarketplace();
            } elseif ($view['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_AVG_IN_RANGE) {
                $view['marketplace_avg'] = $this->getFProduct()->getSyncMarketplace();
            }
        }

        return $view;
    }

    private function prepareLinks($formLinks = null)
    {
        $value = array();
        $value['columns'] = array(
            'marketplace_key' => 'Marketplace',
            'level_field' => 'Stock Level Field',
            'link_sku' => 'Seller SKU'
        );

        $value['template'] = array(
            'marketplace_key' => $this->getElement(
                'select',
                array(
                    'values' => $this->getCommonHelper()->getMarketplacesAsOptionArray(true),
                    'required' => true
                )
            ),
            'level_field' => $this->getElement(
                'select',
                array(
                    'values' => $this->getInvHelper()->getLevelFieldAsOptionArray(),
                    'value' => $this->getInvHelper()->getDefaultLevelField(),
                    'required' => true
                )
            ),
            'link_sku' => $this->getElement(
                'text',
                array(
                    'required' => true,
                    'value' => $this->getMageProduct()->getSku()
                )
            )
        );

        if (!isset($formLinks)) {
            $formLinks = $this->getFProduct()->getLinks();
        }

        foreach ($formLinks as $link) {
            $marketplace = $link['marketplace_key'];
            $value['collection'][] = array(
                'marketplace_key' => $this->getElement(
                    'select',
                    array(
                        'values' => $this->getCommonHelper()->getMarketplacesAsOptionArray(true),
                        'required' => true,
                        'value' => $marketplace
                    )
                ),
                'level_field' => $this->getElement(
                    'select',
                    array(
                        'values' => $this->getInvHelper()->getLevelFieldAsOptionArray(),
                        'value' => $link['level_field'],
                        'required' => true
                    )
                ),
                'link_sku' => $this->getElement(
                    'text',
                    array(
                        'required' => true,
                        'value' => $link['link_sku']
                    )
                )
            );
        }

        return $value;

    }

    /**
     * @return Webtex_FbaInventory_Model_Product
     */
    protected function getFProduct()
    {
        return Mage::registry('fulfillment_inventory');
    }

    /**
     * @return Webtex_FbaInventory_Model_Product
     */
    protected function getSavedForm()
    {
        return Mage::getSingleton('adminhtml/session')->getInvFulfillmentForm();
    }

    /**
     * @return bool|Mage_Catalog_Model_Product
     */
    protected function getMageProduct()
    {
        if (!isset($this->product)) {
            if ($this->getFProduct()) {
                $this->product = Mage::getModel('catalog/product')->load($this->getFProduct()->getMageProductKey());
                if (!$this->product || !$this->product->getId()) {
                    $this->product = false;
                }
            }
        }

        return $this->product;
    }

    /**
     * @return Webtex_FbaInventory_Helper_Data
     */
    protected function getInvHelper()
    {
        return Mage::helper('wfinv');
    }

}