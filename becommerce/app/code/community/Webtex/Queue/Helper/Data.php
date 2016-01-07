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
class Webtex_Queue_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function addJob(Webtex_Queue_Model_Task $task)
    {
        /** @var Webtex_Queue_Model_Job $job */
        $job = Mage::getModel('wqueue/job');
        $job->init($task)->save();
        $job->triggerEvent('add');
        if ($task->isUrgent()) {
            $this->work($job);
        }
        $job->save();
    }

    public function runQueue()
    {
        $finishedTubes = array();
        $job = $this->getNextJob($finishedTubes);
        while ($job) {
            $result = $this->work($job);
            $job->setLocked(0)->save();
            if ($result == Webtex_Queue_Model_Job::RESULT_THROTTLED) {
                $finishedTubes[] = $job->getTube();
            }
            $job = $this->getNextJob($finishedTubes);
        }
        Mage::dispatchEvent('webtex_queue_processing_finished');
    }

    public function work(Webtex_Queue_Model_Job $job)
    {
        $result = Webtex_Queue_Model_Job::RESULT_OK;
        if ($job->getTask()->isReadyForWork()) {
            $result = $job->getTask()->work();
            if ($result == Webtex_Queue_Model_Job::RESULT_OK) {
                if ($job->getTask()->getStatus() == Webtex_Queue_Model_Job::STATUS_DONE) {
                    $job->triggerEvent('success');
                } elseif ($job->getTask()->getStatus() == Webtex_Queue_Model_Job::STATUS_ERROR) {
                    $job->triggerEvent('failure');
                }
            }
        }

        $job->setStatus($job->getTask()->getStatus())->save();

        return $result;
    }

    private function getNextJob($finishedTubes)
    {
        /** @var Webtex_Queue_Model_Resource_Job_Collection $collection */
        $collection = Mage::getModel('wqueue/job')->getCollection();
        $collection->addFieldToFilter('status', Webtex_Queue_Model_Job::STATUS_READY);
        $collection->addFieldToFilter('locked', 0);
        if (count($finishedTubes)) {
            $collection->addFieldToFilter('tube', array('nin' => $finishedTubes));
        }
        $collection->setOrder('priority');
        /** @var Webtex_Queue_Model_Job $job */
        $job = $collection->getFirstItem();

        if ($job && $job->getEntityId()) {
            try {
                $job->getResource()->beginTransaction();
                $job->getResource()->getReadConnection()->select()
                    ->from($job->getResource()->getMainTable())
                    ->where('entity_id = ?', $job->getEntityId())
                    ->forUpdate()
                    ->query();
                $job->setLocked(1);
                $job->save();
                $job->getResource()->commit();
                return $job;
            } catch (Exception $e) {
                $job->getResource()->rollBack();
            }
        }

        return false;
    }

    public function getJobStatusAsOptionArray()
    {
        return array(
            Webtex_Queue_Model_Job::STATUS_READY => 'Ready For Processing',
            Webtex_Queue_Model_Job::STATUS_DONE => 'Done',
            Webtex_Queue_Model_Job::STATUS_ERROR => 'Failure'
        );
    }

}
