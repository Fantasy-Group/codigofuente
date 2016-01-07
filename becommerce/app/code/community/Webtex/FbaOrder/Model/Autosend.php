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
/**
 * Class Webtex_FbaOrder_Model_Autosend
 * @method Webtex_FbaOrder_Model_Autosend setEntityId(int)
 * @method Webtex_FbaOrder_Model_Autosend setSourceStoreKey(string)
 * @method Webtex_FbaOrder_Model_Autosend setSourceShippingMethod(string)
 * @method Webtex_FbaOrder_Model_Autosend setSourceCountryId(string)
 * @method Webtex_FbaOrder_Model_Autosend setSourceZipIsRange(string)
 * @method Webtex_FbaOrder_Model_Autosend setSourceZipCode(string)
 * @method Webtex_FbaOrder_Model_Autosend setDestinationShippingSpeed(string)
 * @method Webtex_FbaOrder_Model_Autosend setDestinationMarketplace(int)
 * @method Webtex_FbaOrder_Model_Autosend setPolicyProduct(int)
 * @method Webtex_FbaOrder_Model_Autosend setSortOrder(int)
 * @method int getEntityId()
 * @method string getSourceStoreKey()
 * @method string getSourceShippingMethod()
 * @method string getSourceCountryId()
 * @method string getSourceZipIsRange()
 * @method string getSourceZipCode()
 * @method string getDestinationShippingSpeed()
 * @method int getDestinationMarketplace()
 * @method int getPolicyProduct()
 * @method int getSortOrder()
 */
class Webtex_FbaOrder_Model_Autosend
    extends Mage_Core_Model_Abstract
{
    const MP_POLICY_ONE = 0;
    const MP_POLICY_MANY = 1;

    const PRODUCT_POLICY_ALL = 0;
    const PRODUCT_POLICY_WHOLE_ITEMS = 1;
    const PRODUCT_POLICY_ANY = 2;

    const SPEED_STANDARD = 'standard';
    const SPEED_PRIORITY = 'priority';
    const SPEED_EXPEDITED = 'expedited';

    protected function _construct()
    {
        parent::_construct();
        $this->_init('wford/autosend');
    }

}