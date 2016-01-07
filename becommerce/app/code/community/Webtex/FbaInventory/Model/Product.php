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
class Webtex_FbaInventory_Model_Product
{
    /**
     * sync types
     **/
    const TYPE_LOCAL = 0;
    const TYPE_MARKETPLACE = 1;
    const TYPE_MIN_IN_RANGE = 2;
    const TYPE_MAX_IN_RANGE = 3;
    const TYPE_AVG_IN_RANGE = 4;

    /**
     * field names to pick up inventory
     **/
    const FIELD_TOTAL_QTY = 'total_qty';
    const FIELD_IN_STOCK_QTY = 'in_stock_qty';
    const FIELD_DEFAULT = 'in_stock_qty';

    protected $syncFields = array(
        self::FIELD_TOTAL_QTY,
        self::FIELD_IN_STOCK_QTY
    );

    protected $syncTypes = array(
        self::TYPE_LOCAL,
        self::TYPE_MARKETPLACE,
        self::TYPE_MIN_IN_RANGE,
        self::TYPE_MAX_IN_RANGE,
        self::TYPE_AVG_IN_RANGE,
    );

    /** @var Mage_Catalog_Model_Product Should contain magento product */
    protected $product;

    /** @var Webtex_FbaInventory_Model_Sync Should contain $product's sync settings. */
    protected $sync;

    /** @var Webtex_FbaInventory_Model_Link[] Array of $product's amazon links */
    protected $links;

    /** @var Webtex_FbaInventory_Model_Stock[] Array of $product's stock levels */
    protected $stock;

    /** @var string[] Array of errors occurred during last validation */
    protected $validationErrors;

    protected $toDelete;

    /**
     * Object constructor.
     * Require magento product id or magento product object as
     * a parameter.
     * Also it's possible to pass array of internal fields
     * to avoid models population.
     * $data = array(
     *  'product' => catalog/product,
     *  'sync' => wfinv/sync,
     *  'links' => array(
     *      wfinv/stock,
     *      wfinv/stock
     *      ...
     *   )
     * )
     * Sync and links are both optional and are populated if missing.
     *
     * @param int | Mage_Catalog_Model_Product | array $data
     **/
    public function __construct($data)
    {
        if (!is_array($data) || $data instanceof Mage_Catalog_Model_Product) {
            $this->load($data);
        } elseif (is_array($data)) {
            $this->fillWithData($data);
        } else {
            throw new InvalidArgumentException("There is no such product");
        }
    }

    /**
     * Construction bassed on magento product object/id
     *
     * @param int | Mage_Catalog_Model_Product $product
     **/
    protected function load($product)
    {
        if (!$product instanceof Mage_Catalog_Model_Product) {
            $product = Mage::getModel('catalog/product')->load($product);
        }
        if (!$product || !$product->getId()) {
            throw new InvalidArgumentException("There is no such product");
        }

        $this->product = $product;
        $this->initSync();
        $this->initLinks();
        $this->initStock();
    }

    /**
     * Construction based on a ready data
     *
     * @param array $data
     **/
    protected function fillWithData($data)
    {
        if (!isset($data['product'])
            || !$data['product'] instanceof Mage_Catalog_Model_Product
            || !$data['product']->getId()
        ) {
            throw new InvalidArgumentException("Invalid product object is passed");
        } else {
            $this->product = $data['product'];
        }

        if (!isset($data['sync'])) {
            $this->initSync();
        } elseif (!$data['sync'] instanceof Webtex_FbaInventory_Model_Sync
            || $data['sync']->getMageProductKey() != $this->product->getId()
        ) {
            throw new InvalidArgumentException("Invalid sync object is passed");
        } else {
            $this->sync = $data['sync'];
        }

        if (!isset($data['links'])) {
            $this->initLinks();
        } else {
            if (!is_array($data['links'])) {
                throw new InvalidArgumentException("array is expected for 'links'");
            } else {
                foreach ($data['links'] as $link) {
                    if (!$link instanceof Webtex_FbaInventory_Model_Link) {
                        throw new InvalidArgumentException("array of wfinv/link is expected for 'links'");
                    } elseif ($link->getMageProductKey() != $this->product->getId()) {
                        throw new InvalidArgumentException("link product key is not equal to current product id");
                    }
                    $this->links[$link->getStockKey()] = $link;
                }
            }
        }

        if (!isset($data['stock'])) {
            $this->initStock();
        } else {
            if (!is_array($data['stock'])) {
                throw new InvalidArgumentException("array is expected for 'stock'");
            } else {
                foreach ($data['stock'] as $stock) {
                    if (!$stock instanceof Webtex_FbaInventory_Model_Stock) {
                        throw new InvalidArgumentException("array of wfinv/stock is expected for 'stock'");
                    } elseif (!isset($this->links[$stock->getEntityId()])) {
                        throw new InvalidArgumentException(
                            "stock with id {$stock->getEntityId()} is not linked with current product"
                        );
                    }
                    $this->stock[$stock->getMarketplaceKey()] = $stock;
                }
            }
        }
    }

    /**
     * Read sync instance value from database.
     * Create the default one if missing in database
     **/
    protected function initSync()
    {
        $sync = Mage::getModel('wfinv/sync')->load($this->product->getId());
        if (!$sync->getMageProductKey()) {
            $sync->setData(
                array(
                    'mage_product_key' => $this->product->getId(),
                    'sync_type' => self::TYPE_LOCAL,
                    'marketplace_ids' => array()
                )
            )->save();
        }

        $this->sync = $sync;
    }

    /**
     * Read collection of stock links from database
     **/
    protected function initLinks()
    {
        $this->links = array();
        /** @var Webtex_FbaInventory_Model_Link[] $collection */
        $collection = Mage::getModel('wfinv/link')->getCollection()
            ->addFieldToFilter('mage_product_key', $this->product->getId());
        foreach ($collection as $link) {
            $this->links[$link->getStockKey()] = $link;
        }
    }

    /**
     * Read collection of stock levels from database
     **/
    protected function initStock()
    {
        $this->stock = array();
        if (count($this->links)) {
            /** @var Webtex_FbaInventory_Model_Stock[] $collection */
            $collection = Mage::getModel('wfinv/stock')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => array_keys($this->links)));
            foreach ($collection as $stock) {
                $this->stock[$stock->getMarketplaceKey()] = $stock;
            }
        }
    }

    public function refresh()
    {
        $this->initSync();
        $this->initLinks();
        $this->initStock();

        return $this;
    }

    /**
     * Link product with given marketplaces list.
     * $stockFields and $customSku are optional arrays to override
     * default setting of pick up field an marketplace sku
     *
     * @param int[] $marketplaces
     * @param string[] $stockFields
     * @param string[] $customSku
     **/
    public function link($marketplaces, $stockFields = array(), $customSku = array())
    {
        foreach ($marketplaces as $marketplaceId) {
            $linkSku = isset($customSku[$marketplaceId]) ? $customSku[$marketplaceId] : $this->product->getSku();
            $levelField = isset($stockFields[$marketplaceId]) ? $stockFields[$marketplaceId] : self::FIELD_DEFAULT;
            if (isset($this->stock[$marketplaceId]) && $this->stock[$marketplaceId]->getLinkSku() != $linkSku) {
                $this->toDelete[] = $this->links[$this->getStockIdByMarketplace($marketplaceId)];
                unset($this->links[$this->getStockIdByMarketplace($marketplaceId)]);
                unset($this->stock[$marketplaceId]);
                $this->stock[$marketplaceId] = $this->getStock($linkSku, $marketplaceId);
                $this->links[$this->getStockIdByMarketplace($marketplaceId)]
                    = $this->getNewLink($this->getStockIdByMarketplace($marketplaceId));
            } elseif (!isset($this->stock[$marketplaceId])) {
                $this->stock[$marketplaceId] = $this->getStock($linkSku, $marketplaceId);
                $this->links[$this->getStockIdByMarketplace($marketplaceId)]
                    = $this->getNewLink($this->getStockIdByMarketplace($marketplaceId));
            }

            if ($levelField != $this->links[$this->getStockIdByMarketplace($marketplaceId)]->getLevelField()) {
                $this->links[$this->getStockIdByMarketplace($marketplaceId)]->setLevelField($levelField);
            }
        }
    }

    /**
     * @param string $sku
     * @param int $marketplace
     * @return Webtex_FbaInventory_Model_Stock
     */
    protected function getStock($sku, $marketplace)
    {
        $stock = Mage::getModel('wfinv/stock')->getCollection()
            ->addFieldToFilter('marketplace_key', $marketplace)
            ->addFieldToFilter('link_sku', $sku)
            ->getFirstItem();
        if (!$stock || !$stock->getEntityId()) {
            /** @var Webtex_FbaInventory_Model_Stock $stock */
            $stock = Mage::getModel('wfinv/stock');
            $stock->setLinkSku($sku);
            $stock->setMarketplaceKey($marketplace);
            $stock->getInStockQty(0);
            $stock->setTotalQty(0);
            $stock->setBlockedQty(0);
            $stock->setIsSyncRequired(1);
            $stock->save();
        }

        return $stock;
    }

    protected function getStockIdByMarketplace($marketplaceId)
    {
        if (isset($this->stock[$marketplaceId])) {
            return $this->stock[$marketplaceId]->getEntityId();
        }

        return false;
    }

    /**
     * @param int $stockId
     * @return Webtex_FbaInventory_Model_Link
     */
    protected function getNewLink($stockId)
    {
        /** @var Webtex_FbaInventory_Model_Link $link */
        $link = Mage::getModel('wfinv/link');
        $link->setMageProductKey($this->product->getEntityId());
        $link->setLevelField(self::FIELD_DEFAULT);
        $link->setStockKey($stockId);

        return $link;
    }

    public function unlink($marketplaces)
    {
        foreach ($marketplaces as $marketplaceId) {
            $stockId = $this->getStockIdByMarketplace($marketplaceId);
            if ($stockId && isset($this->links[$stockId])) {
                if (!$this->links[$stockId]->isObjectNew()) {
                    $this->toDelete[] = $this->links[$stockId];
                }
                unset($this->links[$stockId]);
                unset($this->stock[$marketplaceId]);
            }
        }
        $this->adjustSync();
    }

    protected function adjustSync()
    {
        $marketplaces = $this->sync->getMarketplaceIds();
        $newMarketplaces = array();
        foreach ($marketplaces as $id) {
            if (isset($this->stock[$id])) {
                $newMarketplaces[] = $id;
            }
        }

        if (!count($newMarketplaces)) {
            $this->sync->setSyncType(self::TYPE_LOCAL);
        }
        $this->sync->setMarketplaceIds($newMarketplaces);
    }


    /**
     * Check local stock for current product return true
     * if qty has been changed
     *
     * @return bool
     **/
    public function checkLocalStock($reindex = true)
    {
        if ($this->sync->getSyncType() != self::TYPE_LOCAL) {
            $calculatedStock = $this->calculateLocalStock();
            /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            $stockItem = Mage::getModel('catalogInventory/stock_item');
            if (!$reindex) {
                $stockItem->setProcessIndexEvents(false);
            }
            $stockItem->loadByProduct($this->product);
            if ($stockItem->getManageStock() && $calculatedStock != intval($stockItem->getQty())) {
                $stockItem->setIsInStock(1);
                $stockItem->setQty($calculatedStock)->save();
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate current stock based on sync policy
     *
     * @return int
     **/
    protected function calculateLocalStock()
    {
        $result = 0;
        if ($this->sync->getSyncType() != self::TYPE_LOCAL) {
            $marketplaces = $this->sync->getMarketplaceIds();
            if (!is_array($marketplaces) || count($marketplaces) == 0) {
                throw new LogicException(
                    "Marketplaces array is wrong: " .
                    "array with at least one element is required "
                );
            }
            $levels = $this->getStockLevel($marketplaces);
            foreach ($levels as $mId => $level) {
                if ($level === false) {
                    throw new LogicException(
                        "Missing data for marketplace with id {$mId}"
                    );
                }
                $marketplace = $this->getCommonHelper()->getMarketplace($mId);
                if (!$marketplace
                    || !$marketplace->getStatus()
                ) {
                    unset($levels[$mId]);
                }
            }

            if ($this->sync->getSyncType() == self::TYPE_MARKETPLACE) {
                $result = count($levels) == 1 ? array_shift($levels) : 0;
            } elseif ($this->sync->getSyncType() == self::TYPE_MIN_IN_RANGE) {
                $result = count($levels) ? min($levels) : 0;
            } elseif ($this->sync->getSyncType() == self::TYPE_MAX_IN_RANGE) {
                $result = count($levels) ? max($levels) : 0;
            } elseif ($this->sync->getSyncType() == self::TYPE_AVG_IN_RANGE) {
                $result = count($levels) ? floor(array_sum($levels) / count($levels)) : 0;
            }
        }

        return (int)$result;
    }

    /**
     * Return current amazon stock level for given
     * marketplace/marketplaces or for all linked marketplaces
     *
     * @param int | int[] | false $marketplace
     *
     * @return int | int[]
     **/
    public function getStockLevel($marketplace = false)
    {
        if ($marketplace === false) {
            $result = array();
            foreach ($this->stock as $mId => $stock) {
                $link = $this->links[$stock->getEntityId()];
                $result[$mId] = $stock->getData($link->getLevelField()) - $stock->getBlockedQty();
            }
        } elseif (is_array($marketplace)) {
            $result = array();
            foreach ($marketplace as $mId) {
                if (isset($this->stock[$mId])) {
                    $stock = $this->stock[$mId];
                    $link = $this->links[$stock->getEntityId()];
                    $result[$mId] = $stock->getData($link->getLevelField()) - $stock->getBlockedQty();
                } else {
                    $result[$mId] = false;
                }
            }
        } else {
            if (isset($this->stock[$marketplace])) {
                $stock = $this->stock[$marketplace];
                $link = $this->links[$stock->getEntityId()];
                $result = $stock->getData($link->getLevelField()) - $stock->getBlockedQty();
            } else {
                $result = false;
            }
        }

        return $result;
    }

    public function getLinkSku($marketplace = false)
    {
        if ($marketplace === false) {
            $result = array();
            foreach ($this->stock as $mId => $stock) {
                $result[$mId] = $stock->getLinkSku();
            }
        } elseif (is_array($marketplace)) {
            $result = array();
            foreach ($marketplace as $mId) {
                if (isset($this->stock[$mId])) {
                    $result[$mId] = $this->stock[$mId]->getLinkSku();
                } else {
                    $result[$mId] = false;
                }
            }
        } else {
            if (isset($this->stock[$marketplace])) {
                $result = $this->stock[$marketplace]->getLinkSku();
            } else {
                $result = false;
            }
        }

        return $result;
    }


    public function setSync($syncType, $marketplaces = array())
    {
        if ($syncType == self::TYPE_LOCAL) {
            $marketplaces = array();
        }
        $this->sync->setMarketplaceIds($marketplaces);
        $this->sync->setSyncType($syncType);
    }

    public function validate()
    {
        return $this->validateSync()
        && $this->validateLinks();

    }

    protected function validateSync()
    {
        $errors = false;
        if (!in_array($this->sync->getSyncType(), $this->syncTypes)) {
            $errors = true;
            $this->addValidationError(
                "Illegal sync type: " . $this->sync->getSyncType()
            );
        }

        $marketplaces = $this->sync->getMarketplaceIds();

        if (count($marketplaces) > 1
            && $this->sync->getSyncType() == self::TYPE_MARKETPLACE
        ) {
            $errors = true;
            $this->addValidationError(
                "Only one marketplace is allowed whith givven sync type"
            );
        }

        if ($this->sync->getSyncType() != self::TYPE_LOCAL) {
            $unlinkedMarketplaces = array_diff(
                $marketplaces,
                array_keys($this->stock)
            );

            if (count($unlinkedMarketplaces)) {
                $errors = true;
                $this->addValidationError(
                    "Product is not connected with marketplaces (" .
                    implode(", ", $unlinkedMarketplaces) . ") " .
                    " or they are unknown!"
                );
            }
        }

        return !$errors;


    }

    protected function validateLinks()
    {
        $errors = false;
        foreach ($this->links as $link) {
            if (!in_array($link->getLevelField(), $this->syncFields)) {
                $errors = true;
                $this->addValidationError(
                    "Unknown Level Field " . $link->getLevelField()
                );
            }
        }

        return !$errors;
    }

    protected function clearValidationErrors()
    {
        $this->validationErrors = array();
    }

    protected function addValidationError($error)
    {
        $this->validationErrors[] = $error;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function save()
    {
        if ($this->validate() && $this->prepareTransaction()->save()) {
            $this->toDelete = array();

            return true;
        }

        return false;
    }


    private function prepareTransaction()
    {
        /** @var Webtex_FbaInventory_Model_Transaction $transaction */
        $transaction = Mage::getModel('wfinv/transaction');
        if (!empty($this->toDelete)) {
            $transaction->addObjectToDelete($this->toDelete);
        }
        if ($this->sync->hasDataChanges()) {
            $transaction->addObjectToSave($this->sync);
        }

        foreach ($this->links as $link) {
            if ($link->isObjectNew() || $link->hasDataChanges()) {
                $transaction->addObjectToSave($link);
            }
        }

        return $transaction;
    }

    public function getMageProductKey()
    {
        return $this->product->getEntityId();
    }

    public function getLinks()
    {
        $result = array();
        foreach ($this->stock as $stock) {
            $link = $this->links[$stock->getEntityId()];
            $result[$stock->getMarketplaceKey()] = array(
                'link_sku' => $stock->getLinkSku(),
                'level_field' => $link->getLevelField(),
                'marketplace_key' => $stock->getMarketplaceKey()

            );
        }

        return $result;
    }

    public function getSyncType()
    {
        return $this->sync->getSyncType();
    }

    public function getSyncMarketplace()
    {
        if ($this->sync->getSyncType() == Webtex_FbaInventory_Model_Product::TYPE_MARKETPLACE) {
            return array_shift($this->sync->getMarketplaceIds());
        } elseif ($this->sync->getSyncType() != Webtex_FbaInventory_Model_Product::TYPE_LOCAL) {
            return $this->sync->getMarketplaceIds();
        }

        return false;
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getCommonHelper()
    {
        return Mage::helper('wfcom');
    }
}
