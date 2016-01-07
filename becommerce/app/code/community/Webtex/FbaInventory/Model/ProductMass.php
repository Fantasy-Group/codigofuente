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
class Webtex_FbaInventory_Model_ProductMass
{
    /**
     * Update levels for given marketplaces and sku.
     * Return affected products id's
     *
     *
     * @param int $marketplace
     * @param string $sku
     * @param array $qty array('in_stock_qty' => %qty%, 'total_qty' => %qty%)
     *
     * @throws Exception
     * @return array
     **/
    public function updateLevels($marketplace, $sku, $qty = array())
    {
        return $this->update(
            $marketplace,
            $sku,
            array(
                Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY
                => $qty[Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY],
                Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY
                => $qty[Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY]
            )
        );
    }

    public function subQty($marketplace, $sku, $qty)
    {
        $inStockField = Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY;
        $totalField = Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY;
        $newInStock = new Zend_Db_Expr(
            "if((cast({$inStockField} as signed) - {$qty}) < 0, 0, {$inStockField} - {$qty})"
        );
        $newTotal = new Zend_Db_Expr(
            "if((cast({$totalField} as signed) - {$qty}) < 0, 0, {$totalField} - {$qty})"
        );

        return $this->update(
            $marketplace,
            $sku,
            array(
                $inStockField => $newInStock,
                $totalField => $newTotal
            )
        );
    }

    public function addQty($marketplace, $sku, $qty)
    {
        $inStockField = Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY;
        $totalField = Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY;
        $newInStock = new Zend_Db_Expr(
            "{$inStockField} + {$qty}"
        );
        $newTotal = new Zend_Db_Expr(
            "{$totalField} + {$qty}"
        );

        return $this->update(
            $marketplace,
            $sku,
            array(
                $inStockField => $newInStock,
                $totalField => $newTotal
            )
        );
    }

    public function unlink($marketplace)
    {
    }

    public function getCollection($productIds)
    {
        $fillData = array_fill_keys($productIds, array());
        /** @var Mage_Catalog_Model_Resource_Product_Collection $productCollection */
        $productCollection = Mage::getModel('catalog/product')->getCollection();
        $productCollection->addAttributeToFilter('entity_id', array('in' => $productIds));
        foreach ($productCollection as $product) {
            $fillData[$product->getEntityId()]['product'] = $product;
        }
        /** @var Webtex_FbaInventory_Model_Sync[] $syncCollection */
        $syncCollection = Mage::getModel('wfinv/sync')->getCollection()
            ->addFieldToFilter('mage_product_key', array('in' => $productIds));

        foreach ($syncCollection as $sync) {
            $fillData[$sync->getMageProductKey()]['sync'] = $sync;
        }
        /** @var Webtex_FbaInventory_Model_Link[] $linkCollection */
        $linkCollection = Mage::getModel('wfinv/link')->getCollection()
            ->addFieldToFilter('mage_product_key', array('in' => $productIds));

        foreach ($linkCollection as $link) {
            $fillData[$link->getMageProductKey()]['links'][$link->getStockKey()] = $link;
            $stockRegistry[$link->getStockKey()][] = $link->getMageProductKey();
            $stockIds[] = $link->getStockKey();
        }
        if (isset($stockIds)) {
            /** @var Webtex_FbaInventory_Model_Stock[] $stockCollection */
            $stockCollection = Mage::getModel('wfinv/stock')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $stockIds));

            foreach ($stockCollection as $stock) {
                foreach ($stockRegistry[$stock->getEntityId()] as $productId) {
                    $fillData[$productId]['stock'][$stock->getMarketplaceKey()] = $stock;
                }
            }
        }

        /** @var Webtex_FbaInventory_Model_Product[] $collection */
        $collection = array();
        foreach ($fillData as $productId => $productWithData) {
            if (!isset($productWithData['links'])) {
                $productWithData['links'] = array();
            }
            $collection[$productId] = Mage::getModel('wfinv/product', $productWithData);
        }

        return $collection;
    }

    public function blockQty($marketplace, $sku, $qty)
    {
        return $this->update(
            $marketplace,
            $sku,
            array('blocked_qty' => new Zend_Db_Expr("blocked_qty + {$qty}"))
        );
    }

    public function unblockQty($marketplace, $sku, $qty = false)
    {
        if ($qty === false) {
            $newQty = 0;
        } else {
            $newQty = new Zend_Db_Expr("if((cast(blocked_qty as signed) - {$qty}) < 0, 0, blocked_qty - {$qty})");
        }

        return $this->update($marketplace, $sku, array('blocked_qty' => $newQty));
    }

    public function unblockAndSubQty($marketplace, $sku, $qty)
    {
        $newBlockQty = new Zend_Db_Expr("if((cast(blocked_qty as signed) - {$qty}) < 0, 0, blocked_qty - {$qty})");
        $inStockField = Webtex_FbaInventory_Model_Product::FIELD_IN_STOCK_QTY;
        $totalField = Webtex_FbaInventory_Model_Product::FIELD_TOTAL_QTY;
        $newInStock = new Zend_Db_Expr(
            "if((cast({$inStockField} as signed) - {$qty}) < 0, 0, {$inStockField} - {$qty})"
        );
        $newTotal = new Zend_Db_Expr(
            "if((cast({$totalField} as signed) - {$qty}) < 0, 0, {$totalField} - {$qty})"
        );

        return $this->update(
            $marketplace,
            $sku,
            array(
                $inStockField => $newInStock,
                $totalField => $newTotal,
                'blocked_qty' => $newBlockQty
            )
        );

    }

    public function getStockLevels($marketplace, $sku)
    {
        /** @var Webtex_FbaInventory_Model_Stock $stock */
        $stock = Mage::getModel('wfinv/stock')->getCollection()
            ->addFieldToFilter('marketplace_key', $marketplace)
            ->addFieldToFilter('link_sku', $sku)
            ->getFirstItem();
        if ($stock && $stock->getId()) {
            return array(
                'in_stock_qty' => $stock->getInStockQty(),
                'total_qty' => $stock->getTotalQty(),
                'blocked_qty' => $stock->getBlockedQty()
            );
        }

        return false;
    }

    private function update($marketplace, $sku, $bind)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $stockTable = Mage::getResourceModel('wfinv/stock')->getMainTable();
        $linkTable = Mage::getResourceModel('wfinv/link')->getMainTable();
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->beginTransaction();
        try {
            $select = $writeConnection->select()
                ->from(array('pLink' => $linkTable), array('mage_product_key'))
                ->join(array('pStock' => $stockTable), 'pLink.stock_key = pStock.entity_id')
                ->where('marketplace_key=?', $marketplace)
                ->where('link_sku=?', $sku);
            $products = $writeConnection->fetchCol($select);
            if (count($products)) {
                $writeConnection->update(
                    $stockTable,
                    $bind,
                    array(
                        'marketplace_key = ?' => $marketplace,
                        'link_sku = ?' => $sku
                    )
                );
            }
            $writeConnection->commit();
        } catch (Exception $e) {
            $writeConnection->rollBack();
            throw $e;
        }

        return $products;

    }
}
