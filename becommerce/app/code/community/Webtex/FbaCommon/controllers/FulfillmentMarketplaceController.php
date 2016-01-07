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
class Webtex_FbaCommon_FulfillmentMarketplaceController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('fba_tab');

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_title($this->__('Amazon Marketplaces'));
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('wfcom/marketplace_grid ')->toHtml()
        );
    }

    public function editAction()
    {
        $entityId = $this->getRequest()->getParam('id');
        if ($entityId) {
            $marketplace = Mage::getModel('wfcom/marketplace')->load($entityId);
            if (!$marketplace || !$marketplace->getId()) {
                $this->_getSession()->addError("Marketplace is not found");
                $this->_redirect('*/*/');

                return;
            }
            Mage::register('amazon_marketplace', $marketplace);
            $refresh = $this->getRequest()->getParam('refresh');
            if ($refresh) {
                $this->_getSession()->unsAmazonMarketplaceForm();
            }
        } else {
            Mage::unregister('amazon_marketplace');
            $this->_getSession()->unsAmazonMarketplaceForm();

        }
        $this->loadLayout()
            ->_setActiveMenu('fba_tab')
            ->_title($this->__('Marketplace Settings'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        $this->_getSession()->unsAmazonMarketplaceForm();
        $formData = $this->getRequest()->getParams();
        if (!empty($formData['id'])) {
            $model = Mage::getModel('wfcom/marketplace')->load($formData['id']);
            if (!$model || !$model->getId()) {
                $this->redirectWithError("Marketplace is not found", $formData);
            }
        } else {
            $model = Mage::getModel('wfcom/marketplace');
            unset($formData['id']);
        }
        try {
            $model->setData($formData)->save();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('wfcom')
                    ->__('An error occurred while saving marketplace. Please review the log and try again.')
            );
            Mage::logException($e);

            return;
        }
        $this->_redirect('*/*/');
    }

    protected function redirectWithError($error, $formData)
    {
        if (is_array($error)) {
            foreach ($error as $message) {
                $this->_getSession()->addError($message);
            }
        } else {
            $this->_getSession()->addError($error);
        }
        $redirectData = array();
        if (isset($formData['id'])) {
            $redirectData['id'] = $formData['id'];
        }
        $this->_redirect('*/*/edit', $redirectData);
        $this->_getSession()->setAmazonMarketplaceForm($formData);

        return 0;
    }

    public function deleteAction()
    {
        $formData = $this->getRequest()->getParams();
        if (isset($formData['id'])) {
            $model = Mage::getModel('wfcom/marketplace')->load($formData['id']);
            if ($model && $model->getId()) {
                try {
                    $model->delete();
                } catch (Mage_Core_Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                } catch (Exception $e) {
                    $this->_getSession()->addError(
                        Mage::helper('wfcom')
                            ->__('An error occurred while deleting marketplace. Please review the log and try again.')
                    );
                    Mage::logException($e);

                    return;
                }
                $this->_redirect('*/*/');

                return;
            }
        }
        $this->redirectWithError("Marketplace is not found", $formData);
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function massDeleteAction()
    {
        $this->mass(
            $this->getRequest()->getParam('marketplace'),
            array(
                array(
                    'name' => 'delete',
                    'param' => null
                )
            ),
            'marketplace mass deletion'
        );

    }

    public function massSyncInventoryAction()
    {
        $this->mass(
            $this->getRequest()->getParam('marketplace'),
            array(
                array(
                    'name' => 'syncInventory',
                    'param' => null
                )
            ),
            'marketplace mass inventory synchronization'
        );

    }

    public function massSyncOrdersAction()
    {
        $this->mass(
            $this->getRequest()->getParam('marketplace'),
            array(
                array(
                    'name' => 'syncOrders',
                    'param' => null
                )
            ),
            'marketplace mass orders synchronization'
        );

    }

    public function massDisableAction()
    {
        $this->mass(
            $this->getRequest()->getParam('marketplace'),
            array(
                array(
                    'name' => 'setStatus',
                    'param' => 0
                ),
                array(
                    'name' => 'save',
                    'param' => null
                )
            ),
            'marketplace mass disabling'
        );
    }

    public function massEnableAction()
    {
        $this->mass(
            $this->getRequest()->getParam('marketplace'),
            array(
                array(
                    'name' => 'setStatus',
                    'param' => 1
                ),
                array(
                    'name' => 'save',
                    'param' => null
                )
            ),
            'marketplace mass enabling'
        );
    }

    private function mass($ids, $methods, $operation)
    {
        if (count($ids)) {
            try {
                $marketplaceCollection = Mage::getModel('wfcom/marketplace')->getCollection()
                    ->addFieldToFilter('id', array('in' => $ids));
                foreach ($marketplaceCollection as $marketplace) {
                    foreach ($methods as $method) {
                        $marketplace->$method['name']($method['param']);
                    }
                }

                $this->_getSession()->addSuccess(
                    Mage::helper('wfcom')->__("The {$operation} is successful.")
                );
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('wfcom')
                        ->__("An error occurred while while performing {$operation}. Please review the log and try again.")
                );
                Mage::logException($e);
                $this->_redirect('*/*/');

                return;
            }
        }
        $this->_getSession()->addError(
            Mage::helper('wfcom')->__("Unable to find marketplaces to perform {$operation} on.")
        );
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fba_tab/marketplace');
    }
}