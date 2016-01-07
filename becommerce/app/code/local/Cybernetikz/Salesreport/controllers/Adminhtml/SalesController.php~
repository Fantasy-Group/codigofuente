<?php
/**
*	Author		: 	Cybernetikz
*	Author Email:   info@cybernetikz.com
*	Blog		: 	http://blog.cybernetikz.com
*	Website		: 	http://www.cybernetikz.com
*/

class Cybernetikz_Salesreport_Adminhtml_SalesController extends Mage_Adminhtml_Controller_Action
{
public function indexAction()
    {
        $this->loadLayout()->renderLayout();
    }
    
	public function reportmanageAction()
    {
        $this->loadLayout()->renderLayout();
    }
	
	public function exportCsvAction()
    {
		if ($data = $this->getRequest()->getPost()) {

			$orserstatus = "";
			
			/*====================== Start Code for magento Orders product =====================*/
			//echo "<pre>";
			$reportaddress = Mage::helper('salesreport')->getReportAddress();
			$addtess_title = ($reportaddress=="billing")?"Billing":"Shipping";
			$orders_csv_row ="Period,Order Id,SKU,Item Name,Qty,Unit Price,Row Total,$addtess_title Name,Email,Street Address,City,State,Postcode,Country";
			$orders_csv_row.="\n";
			
			$to=$_REQUEST['to'];
			$from=$_REQUEST['from'];
		
			$to_date = date('Y-m-d' . ' 23:59:59', strtotime($to));
			$from_date = date('Y-m-d' . ' 00:00:00', strtotime($from));
			
			
			if($_REQUEST['show_order_statuses']>0){
				$orserstatus = $_REQUEST['order_statuses'];
				$orders = Mage::getResourceModel('sales/order_collection')
					->addAttributeToSelect('*')
					->addFieldToFilter('created_at', array('from'=>$from_date, 'to'=>$to_date))
					->addFieldToFilter('status', $orserstatus)
					->load();
			}else{
				$orders = Mage::getResourceModel('sales/order_collection')
					->addAttributeToSelect('*')
					->addFieldToFilter('created_at', array('from'=>$from_date, 'to'=>$to_date))
					->load();
			}
					
			$i=0;
			foreach($orders as $key=>$single_order) {
				//print_r($single_order);
				//exit;
				$thisId = $single_order->getId();
				$myOrder=Mage::getModel('sales/order');
				$myOrder->load($thisId);
				
				//Some random fields
				if($reportaddress=="billing"){
									
					$country_id = utf8_decode($myOrder->getBillingAddress()->getCountryId());
					$country = Mage::getModel('directory/country')->load($country_id)->getName();
					
					$name = utf8_decode($myOrder->getBillingAddress()->getFirstname()." ".$myOrder->getBillingAddress()->getLastname());
					
					$billingaddress = $myOrder->getBillingAddress()->getStreet();
					$address = utf8_decode($billingaddress[0]).", ".utf8_decode($billingaddress[1]);
					
					$city=utf8_decode($myOrder->getBillingAddress()->getCity());
					
					$region=utf8_decode($myOrder->getBillingAddress()->getRegion());
					
					$postcode =utf8_decode($myOrder->getBillingAddress()->getPostcode());
					
				}else{
					
					$country_id = utf8_decode($myOrder->getShippingAddress()->getCountryId());
					$country = Mage::getModel('directory/country')->load($country_id)->getName();
					
					$name = utf8_decode($myOrder->getShippingAddress()->getFirstname()." ".$myOrder->getShippingAddress()->getLastname());
					
					$billingaddress = $myOrder->getShippingAddress()->getStreet();
					$address = utf8_decode($billingaddress[0]).", ".utf8_decode($billingaddress[1]);
					
					$city=utf8_decode($myOrder->getShippingAddress()->getCity());
					
					$region=utf8_decode($myOrder->getShippingAddress()->getRegion());
					
					$postcode =utf8_decode($myOrder->getShippingAddress()->getPostcode());
				}
							
				$myOrder->loadByIncrementId($myOrder->getIncrementId());
				//$payment_method = $myOrder->getPayment()->getMethodInstance()->getTitle();
				$store = Mage::app()->getStore();
				$items = $myOrder->getItemsCollection();
				$ic=1;
				$countitems=0;
				
				$item_line="";
				foreach ($items as $itemId => $item){
					
					if($item->getQtyToInvoice()!=0):
						$itemorderqty = $item->getQtyToInvoice();
					else:
						$itemorderqty = round($item->getQtyOrdered());
					endif;
					
					if($item->getParentItemId() && round($item->getOriginalPrice())==0){
						$parentitem = $order->getItemById($item->getParentItemId());

						$originalprice = $parentitem->getOriginalPrice();
						
						$subtotal = ($parentitem->getOriginalPrice()*$itemorderqty);
						
						$discountamount=0;				
						if(round($parentitem->getDiscountAmount())!=0){
							$discountamount=$parentitem->getDiscountAmount();
							$subtotal=($subtotal-$discountamount);
						}
						
						$subtotal = number_format($subtotal,2);
						
						$eachitemdiscountamount = ($discountamount/$itemorderqty);
						$discountamount = number_format($eachitemdiscountamount,2);
						
						$taxpercent = $parentitem->getTaxPercent();
						
						$eachitemvat = $vatamount_eachproduct/$itemorderqty;														
						$totalvatdisamount = $eachitemvat+$eachitemdiscountamount;							
						$net_price = round($originalprice-($totalvatdisamount),2);						
					}else{						
						$originalprice = $item->getOriginalPrice();
						
						$subtotal = ($item->getOriginalPrice()*$itemorderqty);
						
						$discountamount=0;				
						if(round($item->getDiscountAmount())!=0){
							$discountamount=$item->getDiscountAmount();
							$subtotal=($subtotal-$discountamount);
						}
						
						$subtotal = number_format($subtotal,2);
						
						$eachitemdiscountamount = ($discountamount/$itemorderqty);
						$discountamount = number_format($eachitemdiscountamount,2);
						
						$taxpercent = $item->getTaxPercent();
						
						$eachitemvat = $vatamount_eachproduct/$itemorderqty;														
						$totalvatdisamount = $eachitemvat+$eachitemdiscountamount;							
						$net_price = round($originalprice-($totalvatdisamount),2);
					}
										
					$customer_email = "";
					if($custoer_id = $myOrder->getCustomerId()){
						$customer = Mage::getModel('customer/customer')->load($custoer_id);
						$customer_email = $customer->getEmail();
					}
					
					if(empty($customer_email)){
						$customer_email=$myOrder->getCustomerEmail();
					}					

					//echo $myOrder->getCreatedAt();
					//exit;
					$datarow =  array(date("d/m/Y",strtotime($myOrder->getCreatedAt())), $myOrder->getIncrementId(), $item->getSku(),utf8_decode($item->getName()), $itemorderqty, utf8_decode($net_price),$subtotal,$name,$customer_email,$address,$city,$region,$postcode,$country);
					
					//print_r($csvtitles);			
					$line = "";
					$comma = "";
					foreach($datarow as $titlename) {
						$line .= $comma . str_replace(array(','),array(""), $titlename);
						$comma = ",";
					}

					$line .= "\n";
					
					$orders_csv_row .=$line;
									
				}
			}
		$reportname = Mage::helper('salesreport')->getReportName();
		 $fileName   = $reportname.'.csv';
		//print_r($orders_csv_row);
		 //exit;
		 $this->_sendUploadResponse($fileName, $orders_csv_row);
		 
		}
    }
	
	protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
	
	
}