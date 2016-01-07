<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to suporte.developer@buscape-inc.com so we can send you a copy immediately.
 *
 * @category   Buscape
 * @package    Buscape_PayULatam
 * @copyright  Copyright (c) 2010 Buscapé Company (http://www.buscapecompany.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Buscape_PayULatam_StandardController extends Mage_Core_Controller_Front_Action 
{

    /**
     * Order instance
     */
    protected $_order;

    
    public function paymentAction()
    {  
       $this->loadLayout();
       $this->renderLayout();       
    }
    
    public function returnAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    public function paymentbackendAction() 
    {
        $this->loadLayout();
        $this->renderLayout();

        $hash = explode("/order/", $this->getRequest()->getOriginalRequest()->getRequestUri());
        $hashdecode = explode(":", Mage::getModel('core/encryption')->decrypt($hash[1]));

        $order = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('increment_id', $hashdecode[0])
                ->addFieldToFilter('quote_id', $hashdecode[1])
                ->getFirstItem();

        if ($order) {
            $session = Mage::getSingleton('checkout/session');
            $session->setLastQuoteId($order->getData('quote_id'));
            $session->setLastOrderId($order->getData('entity_id'));
            $session->setLastSuccessQuoteId($order->getData('quote_id'));
            $session->setLastRealOrderId($order->getData('increment_id'));
            $session->setPayULatamQuoteId($order->getData('quote_id'));
            $this->_redirect('payulatam/standard/payment/type/standard');
        } else {
            Mage::getSingleton('checkout/session')->addError('URL informada é inválida!');
            $this->_redirect('checkout/cart');
        }
    }

    public function errorAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder() {
        
        if ($this->_order == null) {
            
        }
        
        return $this->_order;
    }

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with payu latam standard order transaction information
     *
     * @return Buscape_PayULatam_Model_Api
     */
    public function getApi() 
    {
        return Mage::getSingleton('payulatam/'.$this->getRequest()->getParam("type"));
    }

    /**
     * When a customer chooses PayU Latam on Checkout/Payment page
     *
     */
    public function redirectAction() 
    {
        /*
         * caso precise para identificar o tipo de modelo.
         * Ex: $this->getResponse()->setBody($this->getLayout()->createBlock('payulatam/redirect_{$type}}')->toHtml());
         */
        
        $type = $this->getRequest()->getParam('type', false);
        
        $session = Mage::getSingleton('checkout/session');

        $session->setPayULatamQuoteId($session->getQuoteId());
        
        $this->getResponse()->setHeader("Content-Type", "text/html; charset=ISO-8859-1", true);

        $this->getResponse()->setBody($this->getLayout()->createBlock('payulatam/redirect')->toHtml());

        $session->unsQuoteId();
    }

    /**
     * When a customer cancel payment from payu latam.
     */
    public function cancelAction() 
    {
        
        $session = Mage::getSingleton('checkout/session');

        $session->setQuoteId($session->getPayULatamQuoteId(true));

        // cancel order
        if ($session->getLastRealOrderId()) {

            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * when payu latam returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the return post.
     */
    public function successAction() 
    {
        $postData = $this->getRequest()->getPost();

        $sign = $postData['sign'];
        
        $apiKey = $this->getApi()->getConfigData('apiKey');
        $merchantID = $this->getApi()->getConfigData('merchantID');
        $referenceCode = $postData['reference_sale'];
        $value = number_format($postData['value'],1);
        $currency = $postData['currency'];
        $statePol = $postData['state_pol'];
        
        $signature = md5($apiKey."~".$merchantID."~".$referenceCode."~".$value."~".$currency."~".$statePol);
        
        if($signature == $sign){
            $orderIdPayU = $postData['reference_pol'];
            $payULatam = Mage::getModel('payulatam/standard')->reportApiPayU($orderIdPayU);
        }else{
            $descricao = "Assinatura digital do pedido diferente da enviada pela PayU!!!";
            $this->_redirect("payulatam/standard/error", array('_secure' => true , 'descricao' => urlencode(utf8_encode($descricao))));
        }
        
    }

}