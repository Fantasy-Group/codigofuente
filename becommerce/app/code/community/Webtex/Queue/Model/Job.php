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
class Webtex_Queue_Model_Job
    extends Mage_Core_Model_Abstract
{
    const STATUS_READY = 0;

    const STATUS_DONE = 1;

    const STATUS_ERROR = 2;

    const RESULT_OK = 0;

    const RESULT_THROTTLED = 1;

    /** @var Webtex_Queue_Model_Task */
    public $task;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('wqueue/job');
    }

    public function init(Webtex_Queue_Model_Task $task)
    {
        $this->task = $task;
        $this->setData(
            array(
                'tube' => $task->getTube(),
                'job_type' => $task->getType()
            )
        );
        $this->setPriority($this->task->getPriority());

        return $this;
    }

    public function getTask()
    {
        return $this->task;
    }

    protected function _beforeSave()
    {
        if (isset($this->task)) {
            $task = clone $this->task;
            $task->beforeSerialize();
            $this->setJob(serialize($task));
        }
        parent::_beforeSave();
        if ($this->isObjectNew()) {
            if (!$this->getCreatedAt()) {
                $this->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
            }
            $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        } elseif ($this->hasDataChanges()) {
            $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        }

        return $this;
    }

    protected function _afterLoad()
    {
        $this->task = unserialize($this->getJob());
        if (is_object($this->task)) {
            $this->task->afterUnserialize();
        }

        return parent::_afterLoad();
    }

    public function triggerEvent($event)
    {
        $event = "on" . ucfirst(strtolower($event));
        if (method_exists($this->task, $event)) {
            $this->task->$event();
        }
    }

}
