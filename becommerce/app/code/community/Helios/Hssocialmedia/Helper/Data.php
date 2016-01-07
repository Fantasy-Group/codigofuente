<?php
class Helios_Hssocialmedia_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
     * Check Module is Enable or Disable
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getModuleStatus()
    {                
        return Mage::getStoreConfig('hsmedia/mediasetting/hsmedia_enabled');
    }
	/**
     * Check we want to Include JS or not
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getJsStatus()
    {                
        return Mage::getStoreConfig('hsmedia/mediasetting/jquery_yesno');
    }
	/**
     * get Share Button Positions
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getButtonPosition()
    {                
        return Mage::getStoreConfig('hsmedia/mediasetting/buttonposition');
    }
	/**
     * get Margin 
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getMargin()
    {                
        return Mage::getStoreConfig('hsmedia/mediasetting/buttonmargin');
    }
	/**
     * get Button Effect
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getEffect()
    {                		
        return Mage::getStoreConfig('hsmedia/mediasetting/buttoneffect');
    }
	/**
     * get facebook link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getFacebookUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/facebookurl');
    }
	public function getFacebookTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/facebooktitle');
    }
	/**
     * get Linkedin link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getLinkidinUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/linkedinurl');
    }
	public function getLinkidinTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/linkedintitle');
    }
	/**
     * get Twitter link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getTwitterUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/twitterurl');
    }
	public function getTwitterTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/twittertitle');
    }
	/**
     * get Youtube link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getYoutubeUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/youtubeurl');
    }
	public function getYoutubeTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/youtubetitle');
    }
	/**
     * get Google + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getGoogleUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/googleplusurl');
    }
	public function getGoogleTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/googleplustitle');
    }
	/**
     * get Pinterest + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
    */
	public function getPinterestUrl()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/pinteresturl');
    }
	public function getPinterestTitle()
    {                		
        return Mage::getStoreConfig('hsmedia/hsmediashare/pinteresttitle');
    }
    /**
     * get Instagram + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
     */
    public function getInstagramUrl()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/instagramurl');
    }
    public function getInstagramTitle()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/instagramtitle');
    }
    /**
     * get Flickr + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
     */
    public function getFlickrUrl()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/flickrurl');
    }
    public function getFlickrTitle()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/flickrtitle');
    }
    /**
     * get Website + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
     */
    public function getWebUrl()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/weburl');
    }
    public function getWebTitle()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/webtitle');
    }
    /**
     * get Email Address + link Details
     *
     * @return Helios_Hssocialmedia_Helper_Data
     */
    public function getMailUrl()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/webmail');
    }
    public function getMailTitle()
    {
        return Mage::getStoreConfig('hsmedia/hsmediashare/webmailtitle');
    }
}
	 