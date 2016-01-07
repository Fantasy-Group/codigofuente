<?php
/**
 * Webtex
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.webtexsoftware.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@webtexsoftware.com and we will send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to http://www.webtexsoftware.com for more information,
 * or contact us through this email: info@webtexsoftware.com.
 *
 * @category   Webtex
 * @package    Webtex_FbaCommon
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_FbaCommon_Helper_Data extends Mage_Core_Helper_Abstract
{

    const AWS_APPLICATION_NAME = 'Webtex_Fba';

    const FBA_MP_LABELS = 'fba_marketplace_labels';
    const FBA_MP = 'fba_marketplaces';

    const CONFIG_TEST_MODE_PATH = 'fba/fba_common/test_mode';
    const CONFIG_AUTOSEND_PATH = 'fba/fba_common/autosend';

    /**
     * get aplication name for client object
     *
     * @return string
     */
    public function getClientApplicationName()
    {
        return self::AWS_APPLICATION_NAME;
    }

    /**
     * get application version for client object
     *
     * @return string
     */
    public function getClientApplicationVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Webtex_FbaCommon->version;
    }

    public function getMarketplaceLabel($marketplaceId)
    {
        $marketplaces = $this->getMarketplacesAsOptionArray();
        if (isset($marketplaces[$marketplaceId])) {
            return $marketplaces[$marketplaceId];
        }

        return "unknown";
    }

    public function getMarketplacesAsOptionArray($removeNoFba = false)
    {
        $cached = Mage::registry(self::FBA_MP_LABELS);
        if (!$cached) {
            $cached[0] = 'no fba';
            $aMarketplaces = Mage::getModel('wfcom/config_source_amazonMarketplace')->toArray();
            foreach (Mage::getModel('wfcom/marketplace')->getCollection() as $marketplace) {
                $cached[$marketplace->getId()] =
                    $aMarketplaces[$marketplace->getAmazonMarketplace()] . "-" . $marketplace->getId();
            }
            Mage::register(
                self::FBA_MP_LABELS,
                $cached
            );
        }
        if ($removeNoFba) {
            unset($cached[0]);
        }

        return $cached;

    }

    public function getSpeedAsOptionArray()
    {
        return array(
            'Standard' => 'Standard',
            'Expedited' => 'Expedited',
            'Priority' => 'Priority'
        );
    }

    public function getModel($modelClass, $constructArguments = array())
    {
        if ($modelClass == 'mwsInv/client' && $this->isTestMode()) {
            $modelClass = 'wfcom/mockInv';
        }
        if ($modelClass == 'mwsOut/client' && $this->isTestMode()) {
            $modelClass = 'wfcom/mockOut';
        }
        $className = Mage::getConfig()->getModelClassName($modelClass);
        if (class_exists($className)) {
            Varien_Profiler::start('CORE::create_object_of::' . $className);
            $reflection = new ReflectionClass($className);
            $obj = $reflection->newInstanceArgs($constructArguments);
            Varien_Profiler::stop('CORE::create_object_of::' . $className);

            return $obj;
        } else {
            return false;
        }

    }

    /**
     * @param $id
     * @return false | Webtex_FbaCommon_Model_Marketplace
     */
    public function getMarketplace($id)
    {
        $cached = Mage::registry(self::FBA_MP);
        if (!$cached) {
            foreach (Mage::getModel('wfcom/marketplace')->getCollection() as $marketplace) {
                $cached[$marketplace->getId()] = $marketplace;
            }
            Mage::register(self::FBA_MP, $cached);
        }

        if (isset($cached[$id])) {
            return $cached[$id];
        }

        return false;
    }

    public function invalidateMarketplaceCache()
    {
        Mage::unregister(self::FBA_MP);
        Mage::unregister(self::FBA_MP_LABELS);
    }

    public function enableTestMode($persistent = false)
    {
        if ($persistent) {
            Mage::getConfig()->saveConfig(self::CONFIG_TEST_MODE_PATH, 1);
        }

        Mage::app()->getStore()->setConfig(self::CONFIG_TEST_MODE_PATH, 1);
    }

    public function disableTestMode($persistent = false)
    {
        if ($persistent) {
            Mage::getConfig()->saveConfig(self::CONFIG_TEST_MODE_PATH, 0);
        }

        Mage::app()->getStore()->setConfig(self::CONFIG_TEST_MODE_PATH, 0);
    }

    public function isTestMode()
    {
        return Mage::getStoreConfig(self::CONFIG_TEST_MODE_PATH);
    }

    public function setAutosendMode($mode, $persistent = true)
    {
        if ($persistent) {
            Mage::getConfig()->saveConfig(self::CONFIG_AUTOSEND_PATH, $mode);
        }

        Mage::app()->getStore()->setConfig(self::CONFIG_AUTOSEND_PATH, $mode);
    }

    public function getAutosendMode()
    {
        return Mage::getStoreConfig(self::CONFIG_AUTOSEND_PATH);
    }

    public function convertOptions($options)
    {
        $result = array();
        foreach ($options as $value => $label) {
            $result[] = array(
                'value' => $value,
                'label' => $label
            );
        }

        return $result;

    }

    /**
     * @return Webtex_FbaCommon_Model_Config_Source_AmazonMarketplace
     */
    public function getEndpointSource()
    {
        return Mage::getModel('wfcom/config_source_amazonMarketplace');

    }

    public function getStatusOptions()
    {
        $options = array();
        foreach (Mage::getModel('adminhtml/system_config_source_enabledisable')->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    public function log($message, $level = null)
    {
        Mage::log($message, $level, 'webtex-amazon-fba.log');
    }
}
