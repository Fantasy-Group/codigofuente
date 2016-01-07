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
class Webtex_FbaInventory_Model_Transaction
{

    /** @var Varien_Object[] List of models with data changes */
    protected $toSave = array();

    /** @var Varien_Object[] List of models to delete */
    protected $toDelete = array();

    protected function startTransaction()
    {
        foreach ($this->toSave as $object) {
            $object->getResource()->beginTransaction();
        }
        foreach ($this->toDelete as $object) {
            $object->getResource()->beginTransaction();
        }
    }

    protected function commitTransaction()
    {
        foreach ($this->toSave as $object) {
            $object->getResource()->commit();
        }
        $this->toSave = array();
        foreach ($this->toDelete as $object) {
            $object->getResource()->commit();
        }
        $this->toDelete = array();
    }

    protected function rollbackTransaction()
    {
        foreach ($this->toSave as $object) {
            $object->getResource()->rollBack();
        }
        foreach ($this->toDelete as $object) {
            $object->getResource()->rollBack();
        }
    }

    public function save()
    {
        try {
            $this->startTransaction();
            foreach ($this->toDelete as $object) {
                $object->delete();
            }
            foreach ($this->toSave as $object) {
                $object->save();
            }
            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
        return true;
    }

    public function addObjectToSave($object)
    {
        if (is_array($object)) {
            $this->toSave += $object;
        } else {
            $this->toSave[] = $object;
        }

    }

    public function addObjectToDelete($object)
    {
        if (is_array($object)) {
            $this->toDelete += $object;
        } else {
            $this->toDelete[] = $object;
        }

    }

}