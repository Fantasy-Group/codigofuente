<<?php 

class Cadena_Checkoutevent_Model_Inventory {
    
    const URLBASE = 'http://52.6.223.21/ecommerce/api/cadenafabricasw/magentoapi'; 
    const LOG_NAME = 'SAG.log';

	public function unloadInventory($order) {
		$serviceUrl = '/descargainventarioerp';
		$amount = $order->getGrandTotal() - $order->getShippingAmount();
		$object = new StdClass;
		$object->nroOrden = $order->getIncrementId();
		$object->nombreCliente = $order->getCustomerName();
		$object->fecha = $order->getCreatedAt();
		$object->correoCliente = $order->getCustomerEmail();
		$object->total = $amount;

		$object->productos = array();

		$items = $order->getAllItems();
		foreach ($items as $item) {
			$product = new StdClass;
			$product->sku = $item->getSku();
			$product->cantidad = $item->getQtyOrdered();
			$product->precio = $item->getPrice();
			$product->codigoBIN = '';
			$product->codigoBodega = '';
			array_push($object->productos, $product);
		}

		$data = json_encode($object);
		$this->executeRestService($serviceUrl, $data);
	}

	public function restoreInventory($order) {
        $serviceUrl = '/reintegrarinventarioerp';
        $amount = $order->getGrandTotal() - $order->getShippingAmount();
		$object = new StdClass;
		$object->nroOrden = $order->getIncrementId();
		$object->nombreCliente = $order->getCustomerName();
		$object->fecha = $order->getCreatedAt();
		$object->correoCliente = $order->getCustomerEmail();
		$object->total = $amount;

		$object->productos = array();

		$items = $order->getAllItems();
		foreach ($items as $item) {
			$product = new StdClass;
			$product->sku = $item->getSku();
			$product->cantidad = $item->getQtyOrdered();
			$product->precio = $item->getPrice();
			$product->codigoBIN = '';
			$product->codigoBodega = '';
			array_push($object->productos, $product);
		}

		$data = json_encode($object);
        $this->executeRestService($serviceUrl, $data);
	}

	private function executeRestService($service, $data) {
        Mage::log('JSON obj: '.$data, null, self::LOG_NAME);
		$serviceUrl = self::URLBASE . $service;
		$curl = curl_init($serviceUrl);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_PUT, true);
		curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode($curl_response);
        Mage::log('Response status: '.$decoded->response->status, null, self::LOG_NAME);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
            $errorMsg = 'Error executing REST service: ' . $serviceUrl;
            Mage::log($errorMsg, null, self::LOG_NAME);
            die($errorMsg);
        } 
	}

}
