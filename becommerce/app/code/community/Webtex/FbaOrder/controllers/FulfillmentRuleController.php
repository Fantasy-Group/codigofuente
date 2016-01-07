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
class Webtex_FbaOrder_FulfillmentRuleController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fba_tab')
            ->_title($this->__('Fulfillment Rules'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('wford/rules_grid')->toHtml()
        );
    }

    public function massDeleteAction()
    {
        $ruleIds = (array)$this->getRequest()->getParam('rule');
        if (count($ruleIds)) {
            try {
                $ruleCollection = Mage::getModel('wford/autosend')->getCollection()
                    ->addFieldToFilter('entity_id', array('in' => $ruleIds));
                foreach ($ruleCollection as $rule) {
                    $rule->delete();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('wfcom')->__('The rules has been deleted.')
                );
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('wfcom')
                        ->__('An error occurred while deleting the rule. Please review the log and try again.')
                );
                Mage::logException($e);
                $this->_redirect('*/*/');

                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('wfcom')->__('Unable to find a rule to delete.')
        );
        $this->_redirect('*/*/');
    }

    public function createAction()
    {
        $this->_title('Amazon')
            ->_title('Fulfillment Rule');

        $this->loadLayout()
            ->_setActiveMenu('fba_tab');
        $this->renderLayout();
    }

    public function editAction()
    {
        $formData = $this->getRequest()->getParams();

        /** @var Webtex_FbaOrder_Model_Autosend $model */
        if ($formData['entity_id']) {
            $model = Mage::getModel('wford/autosend')->load($formData['entity_id']);
            if ($model && $model->getEntityId()) {
                $this->_title('Amazon')
                    ->_title('Fulfillment Rule');
                $this->setFormData($model);

                $this->loadLayout()
                    ->_setActiveMenu('fba_tab');
                $this->renderLayout();

                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('wford')->__("Rule  is not found")
        );
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        $formData = $this->getRequest()->getParams();

        /** @var Webtex_FbaOrder_Model_Autosend $model */
        if ($formData['entity_id']) {
            $model = Mage::getModel('wford/autosend')->load($formData['entity_id']);
            if ($model && $model->getEntityId()) {
                try {
                    $model->delete();
                } catch (Mage_Core_Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                } catch (Exception $e) {
                    $this->_getSession()->addError(
                        Mage::helper('wford')
                            ->__('An error occurred while deleting rule. Please review the log and try again.')
                    );
                    Mage::logException($e);
                    return;
                }
                $this->_redirect('*/*/');

                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('wford')->__("Rule  is not found")
        );
        $this->_redirect('*/*/');
    }

    public function saveAction()
    {
        $formData = $this->getRequest()->getParams();

        /** @var Webtex_FbaOrder_Model_Autosend $model */
        if ($formData['entity_id']) {
            $model = Mage::getModel('wford/autosend')->load($formData['entity_id']);
            if (!$model || !$model->getEntityId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('wford')->__("Rule with id {$formData['entity_id']} is not found")
                );
                $this->setFormData($formData);
                $this->_redirect('*/*/create');
            }
        } else {
            $model = Mage::getModel('wford/autosend');
        }
        $model->setSourceStoreKey($formData['source_store_key']);
        $model->setSourceShippingMethod(
            $formData['source_shipping_method'] != 'custom'
            ? $formData['source_shipping_method']
            : $formData['source_shipping_custom_method']
        );

        $model->setSourceCountryId($formData['source_country_id']);
        $model->setSourceZipIsRange($formData['source_zip_is_range']);
        $model->setSourceZipCode(
            $formData['source_zip_is_range']
            ? $formData['source_zip_from'] . "-" . $formData['source_zip_to']
            : $formData['source_zip']
        );
        $model->setDestinationShippingSpeed($formData['destination_shipping_speed']);
        $model->setDestinationMarketplace($formData['destination_marketplace']);
        $model->setPolicyProduct($formData['policy_product']);
        $model->setSortOrder($formData['sort_order']);
        try {
            $model->save();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('wford')
                    ->__('An error occurred while saving the rule data. Please review the log and try again.')
            );
            Mage::logException($e);
            $this->setFormData($formData);
            return;
        }
        $this->_redirect('*/*/');
    }

    private function setFormData($data)
    {
        if ($data instanceof Webtex_FbaOrder_Model_Autosend) {
            $data = $data->getData();
            if ($data['source_zip_is_range']) {
                list($data['source_zip_from'], $data['source_zip_to']) = explode('-', $data['source_zip_code']);
            } else {
                $data['source_zip'] = $data['source_zip_code'];
            }
            $methods['*'] = '*';
            $methods += Mage::helper('wford')->getShippingMethodsAsOptionArray();
            if (!isset($methods[$data['source_shipping_method']])) {
                $data['source_shipping_custom_method'] = $data['source_shipping_method'];
                $data['source_shipping_method'] = 'custom';
            }
        }
        Mage::register('fulfillment_rule', $data);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fba_tab/fulfillment_rules');
    }
}
