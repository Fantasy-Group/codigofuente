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

class Buscape_PayULatam_Model_Source_TypeCheckout
{
    public function toOptionArray()
    {
        return array(
            array('value'=>"REDIRECT", 'label'=>Mage::helper('adminhtml')->__('Redirecionamento')),
            array('value'=>"FRAME", 'label'=>Mage::helper('adminhtml')->__('Frame')),
            array('value'=>"LIGHTBOX", 'label'=>Mage::helper('adminhtml')->__('LightBox')),
        );
    }
}
