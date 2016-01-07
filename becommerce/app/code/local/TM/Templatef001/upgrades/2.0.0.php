<?php

class TM_Templatef001_Upgrade_2_0_0 extends TM_Core_Model_Module_Upgrade
{
    /**
     * Create featured products, if they are not exists
     */
    public function up()
    {
        $visibility = Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds();
        foreach ($this->getStoreIds() as $storeId) {
            if ($storeId) {
                $store = Mage::app()->getStore($storeId);
            } else {
                $store = Mage::app()->getDefaultStoreView();
            }
            if (!$store) {
                continue;
            }
            $storeId = $store->getId();
            $rootCategory = Mage::getModel('catalog/category')->load($store->getRootCategoryId());

            if (!$rootCategory) {
                continue;
            }
            /**
             * @var Mage_Catalog_Model_Resource_Product_Collection
             */
            $visibleProducts = Mage::getResourceModel('catalog/product_collection');
            $visibleProducts
                ->setStoreId($storeId)
                ->setVisibility($visibility)
                ->addStoreFilter($storeId)
                ->addCategoryFilter($rootCategory)
                ->addAttributeToSort('entity_id', 'desc')
                ->setPageSize(10)
                ->setCurPage(1);

            if (!$visibleProducts->count()) {
                continue;
            }

            foreach ($visibleProducts as $product) {
                $product->load($product->getId());
            }

            // get existing featured products
            $featured = Mage::getResourceModel('catalog/product_collection');
            $featured
                ->setStoreId($storeId)
                ->setVisibility($visibility)
                ->addStoreFilter($storeId)
                ->addCategoryFilter($rootCategory)
                ->setPageSize(1)
                ->setCurPage(1);

            $attributeCode = 'featured';
            if (!$featured->getAttribute($attributeCode)) { // Mage 1.6.0.0 fix
                return;
            }
            $featured->addAttributeToFilter("{$attributeCode}", array('Yes' => true));

            if (!$featured->count()) {
                foreach ($visibleProducts as $product) {
                    // attribute should be saved in global scope
                    if (!in_array(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID, $this->getStoreIds())) {
                        $product->addAttributeUpdate($attributeCode, 0, Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID);
                    }

                    $product->setStoreId($storeId);
                    $product->setFeatured(1);
                    $product->save();
                }
            }
        }
    }

    public function getOperations()
    {
        return array(
            'configuration' => $this->_getConfiguration(),
            'cmsblock'      => $this->_getCmsBlocks(),
            'cmspage'       => $this->_getCmsPages(),
            'productAttribute' => $this->_getProductAttribute()
        );
    }

    private function _getConfiguration()
    {
        return array(
            'design' => array(
                'package/name' => 'f001',
                'theme' => array(
                    'template' => '',
                    'skin'     => '',
                    'layout'   => ''
                )
            ),
            'catalog/product_image/small_width' => 135
        );
    }

    private function _getCmsBlocks()
    {
        return array(
            'contacts' => array(
                'title'      => 'contacts',
                'identifier' => 'contacts',
                'status'     => 1,
                'content'    => <<<HTML
Company Name | USA, NY, Street Address | Phone:  1-800-000-0000
HTML
            ),
            'menu' => array(
                'title' => 'menu',
                'identifier' => 'menu',
                'status' => 1,
                'content' => <<<HTML
<li class="nav-about"><a href="{{store url="about"}}"><span>About Us</span></a></li>
<li class="nav-contacts last"><a href="{{store url="contacts"}}"><span>Contact Us</span></a></li>
HTML
            ),
            'footer_links' => array(
                'title' => 'footer_links',
                'identifier' => 'footer_links',
                'status' => 1,
                'content' => <<<HTML
<div class="box informational">
<ul>
  <li><h6>About us</h6>
    <ul>
      <li><a href="{{store direct_url="about"}}">About Us</a></li>
      <li><a href="{{store direct_url="our-company"}}">Our company</a></li>
      <li><a href="{{store direct_url="catalog/seo_sitemap/category"}}">Sitemap</a></li>
    </ul>
  </li>
  <li><h6>Customer information</h6>
    <ul>
      <li><a href="{{store direct_url="contacts"}}">Contact Us</a></li>
      <li><a href="{{store direct_url="price-matching"}}">Price matching</a></li>
      <li><a href="{{store direct_url="testimonials"}}">Testimonials</a></li>
    </ul>
  </li>
  <li><h6>Security &amp; privacy</h6>
    <ul>
      <li><a href="{{store direct_url="privacy"}}">Privacy Policy</a></li>
      <li><a href="{{store direct_url="safe-shopping"}}">Safe &amp; secure shopping</a></li>
      <li><a href="{{store direct_url="terms"}}">Terms &amp; conditions</a></li>
    </ul>
  </li>
  <li class="last"><h6>Shipping &amp; returns</h6>
    <ul>
      <li><a href="{{store direct_url="delivery"}}">Delivery information</a></li>
      <li><a href="{{store direct_url="guarantees"}}">Satisfaction guarantee</a></li>
      <li><a href="{{store direct_url="returns"}}">Returns policy</a></li>
    </ul>
  </li>
</ul>
</div>
HTML
            )
        );
    }

    private function _getCmsPages()
    {
        return array(
            'home' => array(
                'title'             => 'home',
                'identifier'        => 'home',
                'root_template'     => 'two_columns_right',
                'meta_keywords'     => '',
                'meta_description'  => '',
                'content_heading'   => '',
                'is_active'         => 1,
                'content'           => <<<HTML
<div id="slider">
    <div class="slidercontrolwr">
        <div class="slidercontrol">
            <a href="#section1" title="Slide 1" class="">1</a>
            <a href="#section2" title="Slide 2" class="">2</a>
            <a href="#section3" title="Slide 3" class="">3</a>
            <a href="#section4" title="Slide 4" class="">4</a>
            <a href="#section5" title="Slide 5" class="">5</a>
        </div>
    </div>
    <div class="scroller">
        <div class="content">
            <div class="sectionslide" id="section1">
                <a href="{{store url=""}}"><img src="{{skin url="images/slider/slider1.jpg"}}" alt="" /></a>
            </div>
            <div class="sectionslide" id="section2">
                <a href="{{store url=""}}"><img src="{{skin url="images/slider/slider2.jpg"}}" alt="" /></a>
            </div>
            <div class="sectionslide" id="section3">
                <a href="{{store url=""}}"><img src="{{skin url="images/slider/slider3.jpg"}}" alt="" /></a>
            </div>
            <div class="sectionslide" id="section4">
                <a href="{{store url=""}}"><img src="{{skin url="images/slider/slider4.jpg"}}" alt="" /></a>
            </div>
            <div class="sectionslide" id="section5">
                <a href="{{store url=""}}"><img src="{{skin url="images/slider/slider5.jpg"}}" alt="" /></a>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" charset="utf-8">
    var my_glider = new Glider('slider', {duration:0.5, autoGlide: true, frequency: 4, initialSection: 'section1'});
</script>
HTML
,
                'layout_update_xml' => <<<HTML
<reference name="head">
  <action method="addItem"><type>skin_js</type><name>js/glider.js</name></action>
  <action method="addItem"><type>skin_js</type><name>js/slider.js</name></action>
  <action method="addItem"><type>skin_js</type><name>js/productInfo.js</name></action>
</reference>
<reference name="content">
  <block type="catalog/product_new" name="home.new" alias="product_new" template="catalog/product/new.phtml">
    <action method="setProductsCount"><count>8</count></action>
    <action method="addPriceBlockType">
      <type>bundle</type>
      <block>bundle/catalog_product_price</block>
      <template>bundle/catalog/product/price.phtml</template>
    </action>
  </block>
</reference>
<reference name="right">
  <block type="core/template" name="right.callout1" template="callouts/left_col.phtml" before="-">
    <action method="setImgSrc"><src>images/media/callout_side1.jpg</src></action>
    <action method="setImgAlt" translate="alt" module="catalog"><alt>Call Us Toll Free. (555) 555-555</alt></action>
    <action method="setLinkUrl"><url>checkout/cart</url></action>
  </block>
  <block type="core/template" name="right.callout2" template="callouts/left_col.phtml" after="right.callout1">
    <action method="setImgSrc"><src>images/media/callout_side2.jpg</src></action>
    <action method="setImgAlt" translate="alt" module="catalog"><alt>Free domestic shippings</alt></action>
    <action method="setLinkUrl"><url>checkout/cart</url></action>
  </block>
  <block type="newsletter/subscribe" name="right.newsletter" template="newsletter/subscribe.phtml" after="right.callout2"/>
  <block type="tag/popular" name="tags_popular" template="tag/popular.phtml"/>
</reference>
<reference name="before_footer">
  <block type="featured/featured" name="home.featured" template="catalog/product/featured.phtml">
    <action method="setProductsCount"><count>25</count></action>
  </block>
</reference>
HTML
            )
        );
    }

    private function _getProductAttribute()
    {
        return array(
            array(
                'attribute_code' => 'featured',
                'frontend_label' => array('Featured'),
                'default_value'  => 0
            )
        );
    }
}
