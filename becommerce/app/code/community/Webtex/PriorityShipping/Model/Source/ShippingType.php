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
 * @package    Webtex_PriorityShipping
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_PriorityShipping_Model_Source_ShippingType
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'standard', 'label' => 'Standard'),
            array('value' => 'expedited', 'label' => 'Expedited'),
            array('value' => 'priority', 'label' => 'Priority'),
        );
    }

    public function getNameById($id)
    {
        $array = $this->toOptionArray();
        foreach ($array as $shippingSpeed) {
            if ($shippingSpeed['value'] == $id) {
                return $shippingSpeed['label'];
            }
        }

        return false;
    }

    public function getIdByName($name)
    {
        $name = ucfirst(strtolower($name));
        $array = $this->toOptionArray();
        foreach ($array as $shippingSpeed) {
            if ($shippingSpeed['label'] == $name) {
                return $shippingSpeed['id'];
            }
        }

        return false;
    }

    public function getAllowedMethods()
    {
        return array(
            'standard' => 'Standard Shipping',
            'expedited' => 'Expedited Shipping',
            'priority' => 'Priority Shipping'
        );
    }
}
