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
 * @copyright  Copyright (c) 2010 BuscapÃ© Company (http://www.buscapecompany.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Buscape_PayULatam_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';

    protected $_allowCurrencyCode = array('BRL');
    
    protected $_code  = 'payulatam_standard';
    
    protected $_formBlockType = 'payulatam/form_standard';
    
    protected $_blockType = 'payulatam/standard';
    
    protected $_infoBlockType = 'payulatam/info_standard';
    
    protected $_standardType = 'standard';
    
    protected $_canUseInternal = false;
    
    
    /**
     * Availability options
     */
  

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit()
    {
        return false;
    }
    
    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payulatam/standard/payment', array('_secure' => true, 'type' => 'standard'));
    }
    
     /**
     * Get payulatam session namespace
     *
     * @return Buscape_PayULatam_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('payulatam/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping()
    {
        return false;
    }

    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock($_formBlockType, $name)
            ->setMethod('payulatam')
            ->setPayment($this->getPayment())
            ->setTemplate('buscape/payulatam/form.phtml');
        return $block;
    }

    /*public function assignData($data){
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCheckNo($data->getData())
        ->setCheckDate($data->getCheckDate());
        return $this;
    }*/
    
    public function validate()
    {
        parent::validate();
        $currency_code = $this->getQuote()->getBaseCurrencyCode();
        if (!$currency_code){
            $session = Mage::getSingleton('adminhtml/session_quote');
            $currency_code = $session->getQuote()->getBaseCurrencyCode();            
        } 
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('payulatam')->__('A moeda selecionada ('.$currency_code.') não é compatível com a PayU Latam'));
        }
        $info = $this->getInfoInstance();
        $session = $this->getCheckout();
        $session->setInfoInstance($info);
        
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {
        return $this;
    }

    public function canCapture()
    {
        return true;
    }

    public function getNumEndereco($endereco) 
    {
        $numEndereco = '';

        $posSeparador = $this->getPosSeparador($endereco, false);
        if ($posSeparador !== false)
                    $numEndereco = trim(substr($endereco, $posSeparador + 1));

        $posComplemento = $this->getPosSeparador($numEndereco, true);
        
        if ($posComplemento !== false)
            $numEndereco = trim(substr($numEndereco, 0, $posComplemento));

        if ($numEndereco == '')
            $numEndereco = '?';

        return($numEndereco);
    }

    public function getPosSeparador($endereco, $procuraEspaco = false) 
    {
            $posSeparador = strpos($endereco, ',');
            if ($posSeparador === false)
                    $posSeparador = strpos($endereco, '-');

            if ($procuraEspaco)
                    if ($posSeparador === false)
                            $posSeparador = strrpos($endereco, ' ');

            return($posSeparador);
    }
    
    public function limpaStringPayU($var, $enc = 'UTF-8'){
        $acentos = array(
            'a' => '/À|Á|Â|Ã|Ä|Å/',
            'a' => '/à|á|â|ã|ä|å/',
            'c' => '/Ç/',
            'c' => '/ç/',
            'e' => '/È|É|Ê|Ë/',
            'e' => '/è|é|ê|ë/',
            'i' => '/Ì|Í|Î|Ï/',
            'i' => '/ì|í|î|ï/',
            'n' => '/Ñ/',
            'n' => '/ñ/',
            'o' => '/Ò|Ó|Ô|Õ|Ö/',
            'o' => '/ò|ó|ô|õ|ö/',
            'u' => '/Ù|Ú|Û|Ü/',
            'u' => '/ù|ú|û|ü/',
            'y' => '/Ý/',
            'y' => '/ý|ÿ/',
            'a.' => '/ª/',
            'o.' => '/º/'
        );

        $var = preg_replace($acentos, array_keys($acentos), $var);

        $var = strtolower($var);

        $var = str_replace(" ", "_", $var);

        return $var;
    }
    
    public function getSiglaUfPayU($uf){
        
        $uf = $this->limpaStringPayU($uf);

        $_state_sigla = array(
            'acre' => 'AC',
            'alagoas' => 'AL',
            'amapa' => 'AP',
            'amazonas' => 'AM',
            'bahia' => 'BA',
            'ceara' => 'CE',
            'distrito_federal' => 'DF',
            'espirito_santo' => 'ES',
            'goias' => 'GO',
            'maranhao' => 'MA',
            'mato_grosso' => 'MT',
            'mato_grosso_do_sul' => 'MS',
            'minas_gerais' => 'MG',
            'para' => 'PA',
            'paraiba' => 'PB',
            'parana' => 'PR',
            'pernambuco' => 'PE',
            'piaui' => 'PI',
            'rio_de_janeiro' => 'RJ',
            'rio_grande_do_norte' => 'RN',
            'rio_grande_do_sul' => 'RS',
            'rondonia' => 'RO',
            'roraima' => 'RR',
            'santa_catarina' => 'SC',
            'sao_paulo' => 'SP',
            'sergipe' => 'SE',
            'tocatins' => 'TO'
        );
        
        if(sizeof($uf) > 2){
            return $_state_sigla[$uf];
        }else{
            return strtoupper($uf);
        }
        
    }
    
    public function onlyNumCpf($str) {
        return preg_replace("/[^0-9]/", "", $str);
    }
    
    public function getCheckoutFormFields() 
    {
        Mage::log("getCheckoutFormFields: BEGIN", null, "payulatam.log");
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        
        if (!$orderIncrementId) {
            $quoteidbackend = $this->getCheckout()->getData('payulatam_quote_id');
            $order = Mage::getModel('sales/order')->loadByAttribute('quote_id', $quoteidbackend);
            $orderIncrementId = $order->getData('increment_id');
        }
        else {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        }
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $currency_code = $order->getBaseCurrencyCode();

        list($items, $totals, $discountAmount, $shippingAmount) = Mage::helper('payulatam')->prepareLineItems($order, false, false);

        $cpf = '';

        switch(true) {
            case !is_null($order->getData('customer_taxvat')):
                $cpf = $order->getData('customer_taxvat');
            break;
            case !is_null($order->getCpfCnpj()):
                $cpf = $order->getCpfCnpj();
            break;
        }

        $cpf = $this->onlyNumCpf($cpf);

        if ($items) {
            
            $description = "Pedido ".$orderIncrementId.": ";
            foreach($items as $item) {
                $description .= round($item->getQty(),0) . " x " . $item->getName() . " | ";
            }
            
            $description = substr($description, 0, strlen($description) - 3);
            
            if(strlen($description) > 255){
                $description = substr($description, 0, 250) . '...';
            }
        }
        
        $locale = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(),0,2));
        
        if(!in_array(array("EN", "ES", "PT"),$locale)){
            $locale = "PT";
        }
        
        $totalArr = number_format(round($order->getBaseGrandTotal(),2),2);
        
        $signature = md5($this->getConfigData('apiKey')."~".$this->getConfigData('merchantID')."~".$this->getConfigData('prefixo').$orderIncrementId."~".$totalArr."~".$currency_code);
        
        $sArr = array(
            'merchantId'        => $this->getConfigData('merchantID'),
            'accountId'         => $this->getConfigData('accountID'),
            'referenceCode'     => $this->getConfigData('prefixo').$orderIncrementId,
            'description'       => $description,
            'extra1'            => "MAGENTO BP",
            'amount'            => $totalArr,
            'currency'          => $currency_code,
            'test'              => $this->getConfigData('isSandbox'),
            'signature'         => $signature,
            'buyerEmail'        => $billingAddress->getEmail(),
            'lng'               => $locale,
            'buyerFullName'     => $billingAddress->getFirstname() . ' ' . str_replace("(pj)", "", $billingAddress->getLastname()),
            'payerFullName'     => $billingAddress->getFirstname() . ' ' . str_replace("(pj)", "", $billingAddress->getLastname()),
            'payerDocument'     => $cpf,
            'payerEmail'        => $billingAddress->getEmail(),
            'payerPhone'        => substr(str_replace(" ","",str_replace("(","",str_replace(")","",str_replace("-","",$billingAddress->getTelephone())))),0,2) . substr(str_replace(" ","",str_replace("-","",$billingAddress->getTelephone())),-8),
            'billingAddress'    => $billingAddress->getStreet(1). " " . $billingAddress->getStreet(2),
            'billingCity'       => $billingAddress->getCity(),
            'billingState'      => $this->getSiglaUfPayU($billingAddress->getRegion()),
            'billingCountry'    => $billingAddress->getCountry(),
            'shippingAddress'   => $shippingAddress->getStreet(1). " " . $shippingAddress->getStreet(2),
            'shippingCity'      => $shippingAddress->getCity(),
            'shippingState'     => $this->getSiglaUfPayU($shippingAddress->getRegion()),
            'shippingCountry'   => $shippingAddress->getCountry(),
            'zipCode'           => trim(str_replace("-", "", $shippingAddress->getPostcode())),
        );
        
        $sArr = array_merge($sArr, array('responseUrl' => Mage::getUrl('payulatam/standard/return', array('_secure' => true, '_nosid' =>true))));

        if ($this->getConfigData('retorno') == '1') 
        {            
            $sArr = array_merge($sArr, array('confirmationUrl' => Mage::getUrl('payulatam/standard/success', array('_secure' => true, '_nosid' =>true, 'type' => $this->_standardType))));
        }            
        
        $sReq = '';
        
        $rArr = array();
        
        foreach ($sArr as $k=>$v) {
            $value =  str_replace("&","and",$v);
            $rArr[$k] =  $value;
            $sReq .= '&'.$k.'='.$value;
        }

        if ($this->getDebug() && $sReq) {
            $sReq = substr($sReq, 1);
            $debug = Mage::getModel('payulatam/api_debug')
                    ->setApiEndpoint($this->getPayULatamUrl())
                    ->setRequestBody($sReq)
                    ->save();
        }
        Mage::log("getCheckoutFormFields: Data: \n".$this->getDataLogPayU($rArr), null, "payulatam.log");
        Mage::log("getCheckoutFormFields: END", null, "payulatam.log");
        return $rArr;
    }

    public function getPayULatamUrl($url = 'webCheckout')
    {
        $isSandbox = $this->getConfigData('isSandbox');
        
        $urls = "";
        
        if(!$isSandbox){
            $urls['webCheckout'] = 'https://gateway.payulatam.com/ppp-web-gateway/';
            $urls['paymentApi'] = 'https://api.payulatam.com/payments-api/4.0/service.cgi';
            $urls['reportApi'] = 'https://api.payulatam.com/reports-api/4.0/service.cgi';
            Mage::log("getPayULatamUrl: URL Produção ".$urls[$url], null, "payulatam.log");
        }else{
            $urls['webCheckout'] = 'https://stg.gatewaylap.pagosonline.net/ppp-web-gateway/';
            $urls['paymentApi'] = 'https://stg.api.payulatam.com/payments-api/4.0/service.cgi';
            $urls['reportApi'] = 'https://stg.api.payulatam.com/reports-api/4.0/service.cgi';
            Mage::log("getPayULatamUrl: URL Homologação ".$urls[$url], null, "payulatam.log");
        }
        
        return $urls[$url];
    }

    public function getDebug()
    {
        return Mage::getStoreConfig('payulatam/wps/debug_flag');
    }
    
    public function requestDataPayULatam($urlPost,$params)
    {
        $requestHeader = array("Content-Type: application/json; charset=UTF-8",
			                            "Accept: application/json");
        
        $data = json_encode($params);
	
        Mage::log("requestDataPayULatam: Dados enviados ".$this->getDataLogPayU($params), null, "payulatam.log");
        
	ob_start(); 
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $urlPost); 
	curl_setopt($ch, CURLOPT_POST, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_exec($ch);
        
        $resposta = ob_get_contents(); 
	ob_end_clean(); 
	Mage::log("requestDataPayULatam: Dados retornados ".$this->getDataLogPayU(json_decode($resposta,true)), null, "payulatam.log");
        return json_decode($resposta);
    }
    
    public function getPaymentMethodAbles()
    {
        Mage::log("getPaymentMethodAbles: BEGIN ", null, "payulatam.log");
        
        $url = $this->getPayULatamUrl("paymentApi");
        
        $request = array();
	$request["language"] = "pt";
	$request["test"] = ($this->getConfigData('isSandbox'))? "true":"false";
	$request["command"] = "GET_PAYMENT_METHODS";
	$request["merchant"] = array();
	$request["merchant"]["apiLogin"] = $this->getConfigData('apiLogin');
	$request["merchant"]["apiKey"] = $this->getConfigData('apiKey');
        
        $response = $this->requestDataPayULatam($url,$request);
        
        Mage::log("getPaymentMethodAbles: END ", null, "payulatam.log");
        
        return $response;
                
    }
    
    public function updateStatusPayU($orderId,$orderStatusPayU){
        
        Mage::log("updateStatusPayU: BEGIN ", null, "payulatam.log");
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $statusOrder = "";
        switch ($orderStatusPayU){
            case "NEW" : $statusOrder = Mage_Sales_Model_Order::STATE_NEW; break;
            case "IN_PROGRESS" : $statusOrder = Mage_Sales_Model_Order::STATE_PROCESSING; break;
            case "AUTHORIZED" : $statusOrder = Mage_Sales_Model_Order::STATE_PROCESSING; break;
            case "CAPTURED" : $statusOrder = Mage_Sales_Model_Order::STATE_COMPLETE; break;
            case "CANCELLED" : $statusOrder = Mage_Sales_Model_Order::STATE_CANCELED; break;
            case "DECLINED" : $statusOrder = Mage_Sales_Model_Order::STATE_CANCELED; break;
            case "REFUNDED" : $statusOrder = Mage_Sales_Model_Order::STATE_CANCELED; break;
            default : $statusOrder = $order->getStatus(); break;
        }
        
        Mage::log("updateStatusPayU: Enviado o status ".$orderStatusPayU." e alterado o pedido para o status ".$statusOrder, null, "payulatam.log");
        $order->addStatusToHistory(
            $statusOrder, Mage::helper('payulatam')->__('PayU Latam enviou automaticamente o status: %s', $orderStatusPayU)
        );
        $order->save();
        Mage::log("updateStatusPayU: END ", null, "payulatam.log");
    }
    
    public function reportApiPayU($orderIdPayU){
        Mage::log("reportApiPayU: BEGIN ", null, "payulatam.log");
        $url = $this->getPayULatamUrl("reportApi");
        
        $request = array();
	$request["language"] = "pt";
	$request["test"] = ($this->getConfigData('isSandbox'))? "true":"false";
	$request["command"] = "ORDER_DETAIL";
	$request["merchant"] = array();
	$request["merchant"]["apiLogin"] = $this->getConfigData('apiLogin');
	$request["merchant"]["apiKey"] = $this->getConfigData('apiKey');
        $request["details"] = array();
	$request["details"]["orderId"] = intval($orderIdPayU);
        
        $reportRequest = $this->requestDataPayULatam($url,$request);
        
        $orderId = str_replace($this->getConfigData('prefixo'),'',$reportRequest->result->payload->referenceCode);
        
        $orderStatusPayU = $reportRequest->result->payload->status;
        
        $this->updateStatusPayU($orderId, $orderStatusPayU);
        
        Mage::log("reportApiPayU: END ", null, "payulatam.log");
        return $orderStatusPayU;
    }
    
    public function getInstallmentValuesPayU($totalOrder){
        
	$tax_value = 0.0199;
	
	$tabela_price = array ();
	
	$tabela_price[1] = number_format($totalOrder,2,',','.');
	
	for ($i = 2; $i <= 12; $i++){
            $total = round($totalOrder * (($tax_value * pow((1 + $tax_value),$i)) / (pow((1 + $tax_value),$i)-1)),10);
            if($total >= 5){
                $tabela_price[$i] = number_format($total,2,',','.');
            }
	}
        
        return $tabela_price;
    }
    
    public function getDataLogPayU($params, $prefix = ""){
        $dataLog = "";
        foreach ($params as $key => $value){
            if(gettype($value) == "array"){
                if ($key != "creditCard"){
                    $dataLog .= $this->getDataLogPayU($value,$prefix."[$key]");
                }else{
                    $dataLog .= $prefix."[$key] = XXXXXXXXXXXXXXXX \n";
                }
            }else{                
                $dataLog .= $prefix."[$key] = $value \n";
            }
        }
        return substr($dataLog,0,-2);
    }
    
    public function createTransactionApiPayU(){
        Mage::log("createTransactionApiPayU: BEGIN ", null, "payulatam.log");
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        
        if (!$orderIncrementId) {
            $quoteidbackend = $this->getCheckout()->getData('payulatam_quote_id');
            $order = Mage::getModel('sales/order')->loadByAttribute('quote_id', $quoteidbackend);
            $orderIncrementId = $order->getData('increment_id');
        }
        else {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        }
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $currency_code = $order->getBaseCurrencyCode();

        list($items, $totals, $discountAmount, $shippingAmount) = Mage::helper('payulatam')->prepareLineItems($order, false, false);

        $cpf = '';

        switch(true) {
            case !is_null($order->getData('customer_taxvat')):
                $cpf = $order->getData('customer_taxvat');
            break;
            case !is_null($order->getCpfCnpj()):
                $cpf = $order->getCpfCnpj();
            break;
        }

        $cpf = $this->onlyNumCpf($cpf);

        if ($items) {
            
            $description = "Pedido ".$orderIncrementId.": ";
            foreach($items as $item) {
                $description .= round($item->getQty(),0) . " x " . $item->getName() . " | ";
            }
            
            $description = substr($description, 0, strlen($description) - 3);
            
            if(strlen($description) > 255){
                $description = substr($description, 0, 250) . '...';
            }
        }
        
        $lng = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(),0,2));
        
        if(!in_array($lng,array("en", "es", "pt"))){
            $lng = "pt";
        }
        
        $currency_country = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(),2,2));
        
        if(!in_array($currency_country,array("AR","BR","CL","CO","MX","PA","PE"))){
            $currency_country = "BR";
        }
        
        $totalArr = round($order->getBaseGrandTotal(),2);
        
        $signature = md5($this->getConfigData('apiKey')."~".$this->getConfigData('merchantID')."~".$this->getConfigData('prefixo').$orderIncrementId."~".$totalArr."~".$currency_code);
        
        // Dados necessários para a criação da ordem
        $transactionRequest = array();
        $transactionRequest["language"] = $lng;
        $transactionRequest["command"] = "SUBMIT_TRANSACTION";
        $transactionRequest["test"] = ($this->getConfigData('isSandbox'))? "true":"false";

        // Dados para a autenticação do serviço
        $transactionRequest["merchant"] = array();
        $transactionRequest["merchant"]["apiLogin"] = $this->getConfigData('apiLogin');
        $transactionRequest["merchant"]["apiKey"] = $this->getConfigData('apiKey');

        $transactionRequest["transaction"] = array();
        $transactionRequest["transaction"]["order"] = array();

        // Dados da ordem de compra
        $transactionRequest["transaction"]["order"]["accountId"] = $this->getConfigData('accountID');
        $transactionRequest["transaction"]["order"]["referenceCode"] = $this->getConfigData('prefixo').$orderIncrementId;
        $transactionRequest["transaction"]["order"]["description"] = $description;
        $transactionRequest["transaction"]["order"]["language"] = $lng;
        $transactionRequest["transaction"]["order"]["notifyUrl"] = Mage::getUrl('payulatam/standard/success', array('_secure' => true, '_nosid' =>true, 'type' => $this->_standardType));

        // Dados de entrega da compra
        $transactionRequest["transaction"]["order"]["shippingAddress"] = array();
        $transactionRequest["transaction"]["order"]["shippingAddress"]["street1"] = $shippingAddress->getStreet(1);
        $transactionRequest["transaction"]["order"]["shippingAddress"]["street2"] = $shippingAddress->getStreet(2);
        $transactionRequest["transaction"]["order"]["shippingAddress"]["city"] = $shippingAddress->getCity();
        $transactionRequest["transaction"]["order"]["shippingAddress"]["state"] = $this->getSiglaUfPayU($shippingAddress->getRegion());
        $transactionRequest["transaction"]["order"]["shippingAddress"]["country"] = $shippingAddress->getCountry();
        $transactionRequest["transaction"]["order"]["shippingAddress"]["postalCode"] = trim(str_replace("-", "", $shippingAddress->getPostcode()));

        // Dados do Comprador
        $transactionRequest["transaction"]["order"]["buyer"] = array();
        $transactionRequest["transaction"]["order"]["buyer"]["fullName"] = $shippingAddress->getFirstname() . ' ' . str_replace("(pj)", "", $shippingAddress->getLastname());
        $transactionRequest["transaction"]["order"]["buyer"]["emailAddress"] = $shippingAddress->getEmail();
        $transactionRequest["transaction"]["order"]["buyer"]["contactPhone"] = substr(str_replace(" ","",str_replace("(","",str_replace(")","",str_replace("-","",$shippingAddress->getTelephone())))),0,2) . substr(str_replace(" ","",str_replace("-","",$shippingAddress->getTelephone())),-8);
        $transactionRequest["transaction"]["order"]["buyer"]["dniNumber"] = $cpf;
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"] = array();
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["street1"] = $shippingAddress->getStreet(1);
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["street2"] = $shippingAddress->getStreet(2);
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["city"] = $shippingAddress->getCity();
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["state"] = $this->getSiglaUfPayU($shippingAddress->getRegion());
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["country"] = $shippingAddress->getCountry();
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["postalCode"] = trim(str_replace("-", "", $shippingAddress->getPostcode()));
        $transactionRequest["transaction"]["order"]["buyer"]["shippingAddress"]["phone"] = substr(str_replace(" ","",str_replace("(","",str_replace(")","",str_replace("-","",$shippingAddress->getTelephone())))),0,2) . substr(str_replace(" ","",str_replace("-","",$shippingAddress->getTelephone())),-8);

        // Dados do Pagador da Compra
        $transactionRequest["transaction"]["payer"] = array();
        $transactionRequest["transaction"]["payer"]["fullName"] = $billingAddress->getFirstname() . ' ' . str_replace("(pj)", "", $billingAddress->getLastname());
        $transactionRequest["transaction"]["payer"]["emailAddress"] = $billingAddress->getEmail();
        $transactionRequest["transaction"]["payer"]["contactPhone"] = substr(str_replace(" ","",str_replace("(","",str_replace(")","",str_replace("-","",$billingAddress->getTelephone())))),0,2) . substr(str_replace(" ","",str_replace("-","",$billingAddress->getTelephone())),-8);
        $transactionRequest["transaction"]["payer"]["dniNumber"] = $cpf;
        
        // Dados de cobrança da compra
        $transactionRequest["transaction"]["payer"]["billingAddress"] = array();
        $transactionRequest["transaction"]["payer"]["billingAddress"]["street1"] = $billingAddress->getStreet(1);
        $transactionRequest["transaction"]["payer"]["billingAddress"]["street2"] = $billingAddress->getStreet(2);
        $transactionRequest["transaction"]["payer"]["billingAddress"]["city"] = $billingAddress->getCity();
        $transactionRequest["transaction"]["payer"]["billingAddress"]["state"] = $this->getSiglaUfPayU($billingAddress->getRegion());
        $transactionRequest["transaction"]["payer"]["billingAddress"]["country"] = $billingAddress->getCountry();
        $transactionRequest["transaction"]["payer"]["billingAddress"]["postalCode"] = trim(str_replace("-", "", $billingAddress->getPostcode()));

        // Informações sobre o valor da compra
        $transactionRequest["transaction"]["order"]["additionalValues"] = array();
        $transactionRequest["transaction"]["order"]["additionalValues"]["TX_VALUE"] = array();
        $transactionRequest["transaction"]["order"]["additionalValues"]["TX_VALUE"]["value"] = $totalArr;
        $transactionRequest["transaction"]["order"]["additionalValues"]["TX_VALUE"]["currency"] = $currency_code;

        // Assinatura digital da compra
        $transactionRequest["transaction"]["order"]["signature"] = $signature;

        // Meio de Pagamento.
        $transactionRequest["transaction"]["type"] = "AUTHORIZATION_AND_CAPTURE";
        $transactionRequest["transaction"]["type"] = "AUTHORIZATION_AND_CAPTURE";
        $transactionRequest["transaction"]["paymentMethod"] = $order->getPayment()->getData('cc_type');

        $creditCards = array(1 => 'ELO', 2 => 'MASTERCARD', 3 => 'AMEX', 4 => 'DINERS', 5 => 'VISA');
        
        $info = $this->getCheckout()->getInfoInstance();
        
        // Dados do cartão de crédito
        if(in_array($order->getPayment()->getData('cc_type'),$creditCards)){
            $transactionRequest["transaction"]["creditCard"] = array();
            $transactionRequest["transaction"]["creditCard"]["number"] = $info->getData('cc_number');
            $transactionRequest["transaction"]["creditCard"]["securityCode"] = $info->getData('cc_cid');
            $transactionRequest["transaction"]["creditCard"]["expirationDate"] = $info->getData('cc_exp_year').'/'.$order->getPayment()->getData('cc_exp_month');
            $transactionRequest["transaction"]["creditCard"]["name"] = $info->getData('cc_owner');
        }
        
        // Dados do parcelamento.
        $transactionRequest["transaction"]["extraParameters"] = array();
        $transactionRequest["transaction"]["extraParameters"]["INSTALLMENTS_TYPE"] = "1";
        $transactionRequest["transaction"]["extraParameters"]["INSTALLMENTS_NUMBER"] = "1";
        $transactionRequest["transaction"]["extraParameters"]["extra1"] = "MAGENTO BP";
        
        if ($info->getData('po_number') != null){
            $transactionRequest["transaction"]["extraParameters"]["INSTALLMENTS_NUMBER"] = $info->getData('po_number');
        }

        $transactionRequest["transaction"]["paymentCountry"] = $currency_country;

        // IP do cliente que realizou a compra
        $transactionRequest["transaction"]["ipAddress"] = Mage::helper('core/http')->getRemoteAddr();
        
        
        $order->addStatusToHistory(
            $order->getStatus(), Mage::helper('payulatam')->__('Dados enviados para PayU:<br/>%s', $this->getDataLogPayU($transactionRequest))
        );
        $order->save();
        
        $url = $this->getPayULatamUrl("paymentApi");
        
        $responseRequest = $this->requestDataPayULatam($url,$transactionRequest);
        
        if($responseRequest->code == "SUCCESS"){
            
            $additionalInformation = array(
                "code" => $responseRequest->code,
                "orderId" => $responseRequest->transactionResponse->orderId,
                "transactionId" => $responseRequest->transactionResponse->transactionId,
                "referenceCode" => $orderIncrementId,
                "state" => $responseRequest->transactionResponse->state,
                "paymentMethod" => $order->getPayment()->getData('cc_type'),
                "url_boleto_bancario" => $responseRequest->transactionResponse->extraParameters->URL_BOLETO_BANCARIO,
                "bar_code" => $responseRequest->transactionResponse->extraParameters->BAR_CODE
            );
            
            $additionalInformation["state"] = $this->reportApiPayU($additionalInformation["orderId"]);
            
            $order->getPayment()->setAdditionalInformation($additionalInformation);
            $order->getPayment()->save();
            Mage::log("createTransactionApiPayU: SUCCESS", null, "payulatam.log");
            Mage::log("createTransactionApiPayU: Informações Adicionais de Pagamento: ".$this->getDataLogPayU($additionalInformation), null, "payulatam.log");
            
        }else{
            
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            Mage::log("createTransactionApiPayU: SUCCESS - Informações Adicionais de Pagamento ".$this->getDataLogPayU($additionalInformation), null, "payulatam.log");
            if(sizeof($additionalInformation) > 0){
                Mage::log("createTransactionApiPayU: WARNING", null, "payulatam.log");
                if(in_array($additionalInformation["state"],array("PENDING","NEW","IN_PROGRESS"))){
                    
                    $additionalInformation["state"] = $this->reportApiPayU($additionalInformation["orderId"]);
                    $order->getPayment()->setAdditionalInformation($additionalInformation);
                    $order->getPayment()->save();
                    Mage::log("createTransactionApiPayU: Tentativa de reprocessamento: ".$this->getDataLogPayU($additionalInformation), null, "payulatam.log");
                }
            }else{
                $order->addStatusToHistory(
                    $order->getStatus(), Mage::helper('payulatam')->__('Ocorreu um erro ao processar o pagamento pela PayU: %s', $responseRequest->error)
                );
                $order->save();
                Mage::log("createTransactionApiPayU: Ocorreu um erro ao processar o pagamento pela PayU: ". $responseRequest->error, null, "payulatam.log");
                Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("payulatam/standard/error", array('_secure' => true , 'descricao' => urlencode(utf8_encode($responseRequest->error)))));
            }
        }
        
        Mage::log("createTransactionApiPayU: END ", null, "payulatam.log");
        return $additionalInformation;
        
    }
}