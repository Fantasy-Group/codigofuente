<?php
class Cadena_Checkoutevent_Model_Observer {

   const LOG_NAME = 'OrderObserver.log';

   public function orderSaveAfter($observer) {
    try {
      $order = $observer->getOrder();
      $out = '[ID:'.$order->getIncrementId() . '] - [STATUS:' . $order->getState() . '] - [DATE:' . $order->getCreatedAt() . ']';
      $state = $order->getState();
      $inventoryModel = Mage::getModel('checkoutevent/inventory');
      if (isset($inventoryModel)) {
        Mage::log('Inventory model was found!', null, self::LOG_NAME);
      } else {
        Mage::log('Inventory model was not found!', null, self::LOG_NAME);
      }    
      
      Mage::log('ORDER SAVED: ' . $out, null, self::LOG_NAME);

      if ($state == Mage_Sales_Model_Order::STATE_CANCELED 
          || $state == Mage_Sales_Model_Order::STATE_CLOSED) {
          
          Mage::log('Restore quantities, order #: ' . $order->getIncrementId(), null, self::LOG_NAME);
          $inventoryModel->restoreInventory($order);
                
      } else if ($state == Mage_Sales_Model_Order::STATE_PROCESSING) {
        Mage::log('Order processed, order #: ' . $order->getIncrementId(), null, self::LOG_NAME);
        $inventoryModel->unloadInventory($order);
      }
    } catch (Exception $e) {
      Mage::log($e->getMessage(), null, self::LOG_NAME);
    }
   	
   }



}
