<?php
/**
*	Author		: 	Cybernetikz
*	Author Email:   info@cybernetikz.com
*	Blog		: 	http://blog.cybernetikz.com
*	Website		: 	http://www.cybernetikz.com
*/

class Cybernetikz_Salesreport_Helper_Data extends Mage_Core_Helper_Abstract {
	const REPORT_NAME = "salesreports/reportsetting/report_name";
	const REPORT_ADDRESS = "salesreports/reportsetting/report_address";
	
	public function getReportName($store = null){
		return Mage::getStoreConfig(self::REPORT_NAME, $store);		
	}
	
	public function getReportAddress($store = null){
		return Mage::getStoreConfig(self::REPORT_ADDRESS, $store);		
	}
}