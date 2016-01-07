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
 * @package    Webtex_FbaInventory
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_FbaInventory_Block_ProductsSetting_Renderer_Inventory
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $data = $row->getData($this->getColumn()->getIndex());
        $result = "";
        if (!empty($data)) {
            foreach (explode("\n", $data) as $line) {
                list($marketplaceId, $linkSku, $totalQty, $inStockQty, $levelField)
                    = explode("\t", $line);
                $result .= '<b>Marketplace:</b> ' . Mage::helper('wfcom')->getMarketplaceLabel($marketplaceId)
                    . '; <b>Seller Sku:</b>' . $linkSku;
                if ($levelField == 'total_qty') {
                    $totalColor = '#009c3c';
                    $inStockColor = '#d3d3d3';
                } else {
                    $inStockColor = '#009c3c';
                    $totalColor = '#d3d3d3';
                }
                $result .= "; <span style='color:{$totalColor}'> Total Qty: {$totalQty} </span>"
                    . "; <span style='color:{$inStockColor}'> In Stock Qty: {$inStockQty} </span><br/>";


            }
        }

        return $result;
    }
}