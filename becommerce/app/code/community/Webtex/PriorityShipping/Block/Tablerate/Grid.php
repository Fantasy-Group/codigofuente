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
class Webtex_PriorityShipping_Block_Tablerate_Grid
    extends Mage_Adminhtml_Block_Shipping_Carrier_Tablerate_Grid
{
    /**
     * Prepare shipping table rate collection
     *
     * @return Webtex_PriorityShipping_Block_Tablerate_Grid
     */
    protected function _prepareCollection()
    {
        /** @var $collection Webtex_PriorityShipping_Model_Resource_Tablerate_Collection */
        $collection = Mage::getResourceModel('pShipping/tablerate_collection');
        $collection->setConditionFilter($this->getConditionName())
            ->setWebsiteFilter($this->getWebsiteId());

        $this->setCollection($collection);
    }

    /**
     * Prepare table columns
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'method_code',
            array(
                'header' => Mage::helper('pShipping')->__('Method Code'),
                'index' => 'method_code',
            ),
            'dest_zip'
        );

        return parent::_prepareColumns();
    }
}
