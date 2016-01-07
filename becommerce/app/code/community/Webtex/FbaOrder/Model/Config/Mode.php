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
 * @package    Webtex_FbaOrder
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_FbaOrder_Model_Config_Mode
{
    const DISABLED = 0;
    const WHEN_CREATED = 1;
    const WHEN_INVOICED = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::DISABLED, 'label' => Mage::helper('wford')->__('Never')),
            array('value' => self::WHEN_CREATED, 'label' => Mage::helper('wford')->__('When Order is Created')),
            array('value' => self::WHEN_INVOICED, 'label' => Mage::helper('wford')->__('When Order is Invoiced')),
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
            self::DISABLED => Mage::helper('wford')->__('Never'),
            self::WHEN_CREATED => Mage::helper('wford')->__('When Order is Created'),
            self::WHEN_INVOICED => Mage::helper('wford')->__('When Order is Invoiced'),
        );
    }

}
