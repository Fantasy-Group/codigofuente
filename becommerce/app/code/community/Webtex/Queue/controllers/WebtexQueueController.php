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
 * @package    Webtex_Fba
 * @copyright  Copyright (c) 2011 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */

class Webtex_Queue_WebtexQueueController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('fba_tab')
            ->_title($this->__('Webtex Queue'));
        $this->renderLayout();
    }

    public function sendQueueAction()
    {
        $this->getQueueHelper()->runQueue();
        $this->_redirect('*/*/index');
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('wqueue/queue_grid')->toHtml()
        );
    }

    public function massDeleteAction()
    {
        $jobIds = (array)$this->getRequest()->getParam('job');
        if (count($jobIds)) {
            try {
                $jobCollection = Mage::getModel('wqueue/job')->getCollection()
                    ->addFieldToFilter('entity_id', array('in' => $jobIds));
                foreach ($jobCollection as $job) {
                    $job->delete();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('wqueue')->__('The jobs has been deleted.')
                );
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('wqueue')
                        ->__('An error occurred while deleting the job. Please review the log and try again.')
                );
                Mage::logException($e);
                $this->_redirect('*/*/');

                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('wqueue')->__('Unable to find a job to delete.')
        );
        $this->_redirect('*/*/');
    }

    /**
     * @return Webtex_Queue_Helper_Data
     */
    protected function getQueueHelper()
    {
        return Mage::helper('wqueue');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fba_tab/jobs_queue');
    }
}
