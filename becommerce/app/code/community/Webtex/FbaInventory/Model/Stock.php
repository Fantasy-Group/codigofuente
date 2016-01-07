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

/**
 * Class Webtex_FbaInventory_Model_Stock
 *
 * @method Webtex_FbaInventory_Model_Stock setMarketplaceKey(int $marketplaceId)
 * @method Webtex_FbaInventory_Model_Stock setLinkSku(string $sku)
 * @method Webtex_FbaInventory_Model_Stock setTotalQty(int $qty)
 * @method Webtex_FbaInventory_Model_Stock setInStockQty(int $qty)
 * @method Webtex_FbaInventory_Model_Stock setBlockedQty(int $qty)
 * @method Webtex_FbaInventory_Model_Stock setIsSyncRequired(bool $isRequired)
 * @method int getMarketplaceKey()
 * @method string getLinkSku()
 * @method int getTotalQty()
 * @method int getInStockQty()
 * @method int getBlockedQty()
 * @method bool getIsSyncRequired()
 */
class Webtex_FbaInventory_Model_Stock
    extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('wfinv/stock');
    }
}
