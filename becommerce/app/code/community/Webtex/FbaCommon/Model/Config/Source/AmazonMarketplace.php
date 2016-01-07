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


/**
 * Used in creating options for Amazon Marketplace config value selection
 *
 * CA | https://mws.amazonservices.ca | https://developer.amazonservices.ca
 * CN | https://mws.amazonservices.com.cn | https://developer.amazonservices.com.cn
 * DE | https://mws-eu.amazonservices.com | https://developer.amazonservices.de
 * ES | https://mws-eu.amazonservices.com | https://developer.amazonservices.es
 * FR | https://mws-eu.amazonservices.com | https://developer.amazonservices.fr
 * IN | https://mws.amazonservices.in | https://developer.amazonservices.in
 * IT | https://mws-eu.amazonservices.com | https://developer.amazonservices.it
 * JP | https://mws.amazonservices.jp | https://developer.amazonservices.jp
 * UK | https://mws-eu.amazonservices.com | https://developer.amazonservices.co.uk
 * US | https://mws.amazonservices.com | https://developer.amazonservices.com
 * MX | https://mws.amazonservices.com.mx | https://developer.amazonservices.com
 *
 */
class Webtex_FbaCommon_Model_Config_Source_AmazonMarketplace
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => 'CA'),
            array('value' => 1, 'label' => 'CN'),
            array('value' => 2, 'label' => 'DE'),
            array('value' => 3, 'label' => 'ES'),
            array('value' => 4, 'label' => 'FR'),
            array('value' => 5, 'label' => 'IN'),
            array('value' => 6, 'label' => 'IT'),
            array('value' => 7, 'label' => 'JP'),
            array('value' => 8, 'label' => 'UK'),
            array('value' => 9, 'label' => 'US'),
            array('value' => 10, 'label' => 'MX'),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => 'CA',
            1 => 'CN',
            2 => 'DE',
            3 => 'ES',
            4 => 'FR',
            5 => 'IN',
            6 => 'IT',
            7 => 'JP',
            8 => 'UK',
            9 => 'US',
            10 => 'MX',
        );
    }

    /**
     * Get options in "key-endpointUrl" format
     *
     * @return array
     */
    public function toEndpointUrlArray()
    {
        return array(
            0 => 'https://mws.amazonservices.ca',
            1 => 'https://mws.amazonservices.com.cn',
            2 => 'https://mws-eu.amazonservices.com',
            3 => 'https://mws-eu.amazonservices.com',
            4 => 'https://mws-eu.amazonservices.com',
            5 => 'https://mws.amazonservices.in',
            6 => 'https://mws-eu.amazonservices.com',
            7 => 'https://mws.amazonservices.jp',
            8 => 'https://mws-eu.amazonservices.com',
            9 => 'https://mws.amazonservices.com',
            10 => 'https://mws.amazonservices.com.mx',
        );
    }

}
