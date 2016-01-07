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
 * Class Webtex_FbaInventory_Model_Sync
 *
 * @method Webtex_FbaInventory_Model_Sync setMageProductKey(int $productId)
 * @method Webtex_FbaInventory_Model_Sync setSyncType(int $type)
 * @method Webtex_FbaInventory_Model_Sync setMarketplaceIds(array $marketplaceIds)
 * @method int getMageProductKey()
 * @method int getSyncType()
 * @method array getMarketplaceIds()
 */
class Webtex_FbaInventory_Model_Sync
    extends Mage_Core_Model_Abstract
{
    protected $toSerialize = array(
        'marketplace_ids'
    );

    protected function _construct()
    {
        parent::_construct();
        $this->_init('wfinv/sync');
    }

    protected function _beforeSave()
    {
        $this->serializeAttr();

        return parent::_beforeSave();
    }

    protected function _afterLoad()
    {
        $this->unserializeAttr();

        return parent::_afterLoad();
    }

    protected function _afterSave()
    {
        $this->unserializeAttr();

        return parent::_afterSave();
    }

    protected function serializeAttr()
    {
        foreach ($this->toSerialize as $attr) {
            if (isset($this->_data[$attr])) {
                $this->_data[$attr] = serialize($this->_data[$attr]);
            }
        }

        return $this;
    }

    protected function _hasModelChanged()
    {
        foreach ($this->toSerialize as $attr) {
            if (isset($this->_data[$attr])) {
                $newValue = serialize($this->_data[$attr]);
                if (!isset($this->_origData[$attr])
                    || $newValue != $this->_origData[$attr]
                ) {
                    $this->_hasDataChanges = true;
                    break;
                }
            }
        }
        return parent::_hasModelChanged();
    }

    protected function unserializeAttr()
    {
        foreach ($this->toSerialize as $attr) {
            if (isset($this->_data[$attr])) {
                $this->_data[$attr] = unserialize($this->_data[$attr]);
            }
        }

        return $this;
    }
}
