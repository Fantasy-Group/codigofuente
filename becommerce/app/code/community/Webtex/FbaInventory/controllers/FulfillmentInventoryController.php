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
class Webtex_FbaInventory_FulfillmentInventoryController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fba_tab')
            ->_title($this->__('Products Settings'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('wfinv/productsSetting_grid')->toHtml()
        );
    }

    public function editAction()
    {
        $entityId = $this->getRequest()->getParam('entity_id');
        if ($entityId) {
            try {
                $model = Mage::getModel('wfinv/product', $entityId);
            } catch (InvalidArgumentException $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
            Mage::register('fulfillment_inventory', $model);
            $refresh = $this->getRequest()->getParam('refresh');
            if ($refresh) {
                $this->_getSession()->unsInvFulfillmentForm();
            }
            $this->loadLayout()
                ->_setActiveMenu('fba_tab')
                ->_title($this->__('Product Settings'));
            $this->renderLayout();
            return;
        }
        $this->_getSession()->addError("Product is not found");
        $this->_redirect('*/*/');
    }

    public function saveAction()
    {
        $this->_getSession()->unsInvFulfillmentForm();
        $formData = $this->getRequest()->getParams();
        /** @var Webtex_FbaInventory_Model_Product $model */
        $model = Mage::getModel('wfinv/product', $formData['entity_id']);
        if (!$model->getMageProductKey()) {
            return $this->redirectWithError("Product is not found", $formData);
        }

        $errors = $this->validateForm($formData);
        if (count($errors)) {
            return $this->redirectWithError($errors, $formData);
        }

        /** linking */
        $linkGrid = $formData['links_grid'];
        $currentLinks = $model->getLinks();
        $linkMarketplaces = array();
        $stockFields = array();
        $customSkus = array();
        foreach ($linkGrid as $link) {
            if (in_array($link['marketplace_key'], $linkMarketplaces)) {
                return $this->redirectWithError(
                    'Only one link with particular marketplace is possible',
                    $formData
                );
            } else {
                $linkMarketplaces[] = $link['marketplace_key'];
                if (isset($link['level_field'])) {
                    $stockFields[$link['marketplace_key']] = $link['level_field'];
                }
                if (isset($link['link_sku'])) {
                    $customSkus[$link['marketplace_key']] = $link['link_sku'];
                }
                if (isset($currentLinks[$link['marketplace_key']])) {
                    unset($currentLinks[$link['marketplace_key']]);
                }
            }
        }

        if (count($linkMarketplaces)) {
            $model->link($linkMarketplaces, $stockFields, $customSkus);
        }
        if (count($currentLinks)) {
            $model->unlink(array_keys($currentLinks));
        }

        /** stock settings */
        $stockMarketplaces = array();
        if ($formData['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE) {
            $stockMarketplaces = array($formData['marketplace']);
        } elseif ($formData['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MAX_IN_RANGE) {
            $stockMarketplaces = $formData['marketplace_max'];
        } elseif ($formData['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_AVG_IN_RANGE) {
            $stockMarketplaces = $formData['marketplace_avg'];
        } elseif ($formData['sync_type'] == Webtex_FbaInventory_Model_Product::TYPE_MIN_IN_RANGE) {
            $stockMarketplaces = $formData['marketplace_min'];
        }

        if ($formData['sync_type'] != Webtex_FbaInventory_Model_Product::TYPE_LOCAL) {
            foreach ($stockMarketplaces as $marketplace) {
                if ($model->getStockLevel($marketplace) === false) {
                    return $this->redirectWithError(
                        'Check sync marketplaces, only marketplaces which linked with product are allowed',
                        $formData
                    );
                }
            }
        }

        $model->setSync($formData['sync_type'], $stockMarketplaces);

        try {
            $model->save();
            $model->checkLocalStock();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('wfinv')
                    ->__('An error occurred while editing product settings. Please review the log and try again.')
            );
            Mage::logException($e);
            return;
        }
        $this->_redirect('*/*/');
    }

    protected function redirectWithError($error, $formData) {
        if (is_array($error)) {
            foreach ($error as $message) {
                $this->_getSession()->addError($message);
            }
        } else {
            $this->_getSession()->addError($error);
        }
        $this->_redirect('*/*/edit', array('entity_id' => $formData['entity_id']));
        $this->_getSession()->setInvFulfillmentForm($formData);
        return 0;
    }

    protected function validateForm($formData)
    {
        $errors = array();
        return $errors;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fba_tab/product_settings');
    }
}
