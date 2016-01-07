<?php
/*
 *  Created on May 3, 2011
 *  Author - Ecommerceoffice.com
 *  Copyright Ecommerceoffice.com © 2011. All Rights Reserved.
 *  Single Use, Limited Licence and Single Use No Resale Licence ["Single Use"]
 */
?>
<?php

class Ecommerceoffice_Homepage_Block_Topsell extends Mage_Catalog_Block_Product_Abstract {


	protected function _construct() {
		parent::_construct();
		$this->addData(array(
			'cache_lifetime' => 86400,
			'cache_tags' => array('ecommerceofficehomepage_home_topsell'),
		));

	}
        
        protected function _beforeToHtml() {

		$storeId = Mage::app()->getStore()->getId();
                $sellDate=$this->getModel()->getSellDate($this->getModel()->getHomepageDaysLimit());
		$collection = Mage::getResourceModel('reports/product_collection')
                                ->addOrderedQty()
                                ->addAttributeToSelect("*")
                                ->addAttributeToSelect(array('name','price','small_image'))
                                ->setStoreId($storeId)
                                ->addStoreFilter($storeId)
				//->setDateRange($sellDate['startdate'], $sellDate['todaydate']) //
                                ->addUrlRewrite()
//				->addAttributeToFilter('visibility', array('in' => array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)))
				//->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                                ->setOrder('ordered_qty', 'desc')
                                ->setPageSize($this->getModel()->getTopsellCount())
                                ->setCurPage(1)
				->setOrder('ordered_qty', 'desc');
                Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
                Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

                $catId = Mage::getStoreConfig('homepage/topsell/homecat');
                if ($catId>0) {
                    $category = $this->getModel()->getCategory($catId);
                    $collection->addCategoryFilter($category); 
                }


		$this->setProductCollection($collection);
		return parent::_beforeToHtml();
        //return $collection;
	}

    public function getTitle() {
        return Mage::getStoreConfig('homepage/topsell/title') ;
    }
    public function getModel() {
        return Mage::getModel('homepage/Data');
    }

}














//				->setDateRange($sellDate['startdate'], $sellDate['todaydate'])
//				->addAttributeToFilter('is_salable')
//				->addAttributeToFilter('visibility', array('in' => array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)))
//				->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
//                                ->addSaleableFilterToCollection()
//                                ->addInStockFilterToCollection()
//                                ->addUrlRewrite()
////                                ->addCategoryFilter($currentCategory)
//                                ->setPageSize($this->getModel()->getHomepageProductsLimit())
//                                ->setCurPage(1)
//				->addOrderedQty()
//				->setOrder('ordered_qty', 'desc');
//

//			->addAttributeToSelect(array('entity_id', 'name', 'price', 'small_image', 'short_description', 'description', 'type_id', 'status'))
//			->addOrderedQty()
//			->setStoreId($storeId)
//			->addStoreFilter($storeId)
////			->addCategoryFilter($currentCategory)
//			->setOrder('ordered_qty', 'desc')
//                        ->setPageSize($this->getModel()->getHomepageProductsLimit())
//                        ->setCurPage(1);
//
//                $collection= array();
//                foreach ($rawcollection as $product) {
////                    $addproduct = $product->getData('is_salable');
//                    $collection[]=$product->getData();
//                }
//                var_dump($rawcollection);
////
//
//                exit();