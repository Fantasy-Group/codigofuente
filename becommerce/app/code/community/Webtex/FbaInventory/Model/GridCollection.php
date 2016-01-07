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
 * Class Webtex_FbaInventory_Model_Link
 *
 * @method Webtex_FbaInventory_Model_Link setMageProductKey(int $productId)
 * @method Webtex_FbaInventory_Model_Link setStockKey(int $stockId)
 * @method Webtex_FbaInventory_Model_Link setLevelField(string $levelField)
 * @method int getEntityId()
 * @method int getMageProductKey()
 * @method int getStockKey()
 * @method string getLevelField()
 *
 */
class Webtex_FbaInventory_Model_GridCollection
extends Mage_Catalog_Model_Resource_Product_Collection {
    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::COLUMNS);

        $countSelect->columns('COUNT(distinct e.entity_id)');

        return $countSelect;
    }

}