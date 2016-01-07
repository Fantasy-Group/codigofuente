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
 * @package    Webtex_Queue
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_Queue_Block_Queue extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _construct()
    {
        $this->_blockGroup = 'wqueue';
        $this->_controller = 'queue';
        $this->_headerText = Mage::helper('wfinv')->__('Job Queue');

    }

    public function __construct()
    {
        parent::__construct();
        $this->removeButton('add');
        $this->_addButton('send_queue', array(
            'label'     => Mage::helper('wqueue')->__('Send Queue'),
            'onclick'   => 'setLocation(\'' . $this->getSendUrl() .'\')',
            'class'     => 'add',
        ));
    }

    public function getSendUrl()
    {
        return $this->getUrl('*/*/sendQueue');
    }

}