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
abstract class Webtex_Queue_Model_Task
{
    protected $tube = 'default';

    protected $status = Webtex_Queue_Model_Job::STATUS_READY;

    protected $priority = 0;

    private $errors = array();

    abstract public function work();

    abstract public function toString();

    public function isReadyForWork()
    {
        return true;
    }

    public function isUrgent()
    {
        return false;
    }

    public function onAdd()
    {
        return $this;
    }

    public function onFailure()
    {
        return $this;
    }

    public function onSuccess()
    {
        return $this;
    }

    protected function addError($error)
    {
        $this->errors[] = $error;
    }

    protected function getErrors()
    {
        return $this->errors;
    }

    public function getTube()
    {
        return $this->tube;
    }

    public function getType()
    {
        return get_class($this);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }


    public function beforeSerialize()
    {
        return $this;
    }

    public function afterUnserialize()
    {
        return $this;
    }


}
