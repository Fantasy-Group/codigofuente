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
 * @package    Webtex_FbaCommon
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */

abstract class Webtex_FbaCommon_Model_Task extends Webtex_Queue_Model_Task
{
    /** @var int */
    protected $marketplaceId;

    /** @var Webtex_FbaCommon_Model_Marketplace */
    protected $marketplace;

    /** @var FBAInventoryServiceMWS_Interface */
    protected $client;

    /** @var string */
    protected $url = '';

    /** @var string  */
    protected $clientType = '';

    function __construct($marketplace)
    {
        if ($marketplace instanceof Webtex_FbaCommon_Model_Marketplace) {
            $this->marketplace = $marketplace;
        } else {
            $this->marketplace = Mage::getModel('wfcom/marketplace')->load($marketplace);
        }
        if (!$this->marketplace instanceof Webtex_FbaCommon_Model_Marketplace
            || !$this->marketplace->getId()
        ) {
            throw new LogicException("Wrong marketplace object or primary key is passed");
        }
        $this->marketplaceId = $this->marketplace->getId();
    }

    public function isReadyForWork()
    {
        if (!$this->marketplace
            || !$this->marketplace->getId()
            || !$this->marketplace->getStatus()
        ) {
            $this->addError('Marketplace is missing or disabled');
            $this->status = Webtex_Queue_Model_Job::STATUS_ERROR;
            return false;
        }
        return parent::isReadyForWork();
    }

    protected function getClient()
    {
        if (!isset($this->client)) {
            $this->client = $this->getCommonHelper()->getModel(
                $this->clientType,
                array(
                    $this->marketplace->getAccessKeyId(),
                    $this->marketplace->getSecretKey(),
                    $this->marketplace->getClientConfig($this->url),
                    $this->getCommonHelper()->getClientApplicationName(),
                    $this->getCommonHelper()->getClientApplicationVersion()
                )
            );
        }

        return $this->client;
    }

    /**
     * Get Webtex Fba common helper
     *
     * @return Webtex_FbaCommon_Helper_Data
     */
    protected function getCommonHelper()
    {
        return Mage::helper('wfcom');
    }

    /**
     * @param FBAInventoryServiceMWS_Exception | FBAOutboundServiceMWS_Exception $e
     *
     * @return int
     */
    protected function handleError(Exception $e)
    {
        $this->addError($this->parseErrorMessage($e));
        if ($e->getStatusCode() == '503' && $e->getErrorMessage() == 'RequestThrottled'
            || $e->getStatusCode() == '503' && $e->getErrorMessage() == 'QuotaExceeded'
        ) {
            return Webtex_Queue_Model_Job::RESULT_THROTTLED;
        } else {
            $this->status = Webtex_Queue_Model_Job::STATUS_ERROR;
        }

        return Webtex_Queue_Model_Job::RESULT_OK;

    }

    /**
     * @param FBAInventoryServiceMWS_Exception | FBAOutboundServiceMWS_Exception $e
     *
     * @return int
     */
    private function parseErrorMessage(Exception $e)
    {
        return "Code: " . $e->getErrorCode() . " | "
        . "Status Code: " . $e->getStatusCode() . " | "
        . "Type: " . $e->getErrorType() . " | "
        . "Message: " . $e->getErrorMessage();
    }

    /**
     * Get Webtex Queue helper
     * @return Webtex_Queue_Helper_Data
     */
    protected function getQueueHelper()
    {
        return Mage::helper('wqueue');

    }

    public function getTube()
    {
        return parent::getTube() . "-" . $this->marketplace->getCode();
    }

    public function beforeSerialize()
    {
        unset($this->marketplace);
        return parent::beforeSerialize();
    }

    public function afterUnserialize()
    {
        $this->marketplace = Mage::getModel('wfcom/marketplace')->load($this->marketplaceId);
        return parent::afterUnserialize();
    }
}
