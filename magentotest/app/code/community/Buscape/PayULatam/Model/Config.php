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

class Buscape_PayULatam_Model_Config extends Varien_Object
{
    const XML_PATH = 'payment/payulatam_standard/';
    
    const XML_PATH_CONFIG = 'payulatam/settings/';
    
    protected $_config = array();
    
    protected $_config_settings = array();
    
    public function getConfigData($key, $storeId = null)
    {
        if (!isset($this->_config[$key][$storeId])) {
            $value = Mage::getStoreConfig(self::XML_PATH . $key, $storeId);
            $this->_config[$key][$storeId] = $value;
        }
        return $this->_config[$key][$storeId];
    }

    public function getConfigDataSettings($key, $storeId = null)
    {
        if (!isset($this->_config_settings[$key][$storeId])) {
            $value = Mage::getStoreConfig(self::XML_PATH_CONFIG . $key, $storeId);
            $this->_config_settings[$key][$storeId] = $value;
        }
        return $this->_config_settings[$key][$storeId];
    }    
    
    public function getAccount($store = null)
    {
        if (!$this->hasData('payulatam_account')) {
            $this->setData('payulatam_account', $this->getConfigData('account', $storeId));
        }
        
        return $this->getData('payulatam_account');
    }
    
    public function getToken($store = null)
    {
        if (!$this->hasData('payulatam_token')) {
            $this->setData('payulatam_token', $this->getConfigData('token', $storeId));
        }
        
        return $this->getData('payulatam_token');
    }
    
    public function getUrl($store = null)
    {
        if (!$this->hasData('payulatam_url')) {
            $this->setData('payulatam_url', $this->getConfigData('url', $storeId));
        }
        
        return $this->getData('payulatam_url');
    }
    
    public function getTypeCheckout($storeId = null)
    {        
        if (!$this->hasData('typeCheckout')) {
            $this->setData('typeCheckout', $this->getConfigData('typeCheckout', $storeId));
        }
        
        return $this->getData('typeCheckout');
    }
    
    public function getTypeIntegration($storeId = null)
    {        
        if (!$this->hasData('typeIntegration')) {
            $this->setData('typeIntegration', $this->getConfigData('typeIntegration', $storeId));
        }
        
        return $this->getData('typeIntegration');
    }
    
}