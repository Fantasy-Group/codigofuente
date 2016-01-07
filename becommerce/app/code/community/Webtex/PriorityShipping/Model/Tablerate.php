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
 * @package    Webtex_PriorityShipping
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_PriorityShipping_Model_Tablerate extends Mage_Shipping_Model_Carrier_Tablerate
{
    /**
     * code name
     *
     * @var string
     */
    protected $_code = 'webtexPriority';


    /**
     * Get Rate
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return Mage_Core_Model_Abstract
     */
    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        return Mage::getResourceModel('pShipping/tablerate')->getRate($request);
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        if ($request->getAllItems()) {
            $freePackageValue = 0;
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                            $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                    $freeQty += $item->getQty() - $freeShipping;
                    $freePackageValue += $item->getBaseRowTotal();
                }
            }
            $oldValue = $request->getPackageValue();
            $request->setPackageValue($oldValue - $freePackageValue);
        }

        if ($freePackageValue) {
            $request->setPackageValue($request->getPackageValue() - $freePackageValue);
        }
        if (!$request->getConditionName()) {
            $conditionName = $this->getConfigData('condition_name');
            $request->setConditionName($conditionName ? $conditionName : $this->_default_condition_name);
        }

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();
        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

        $result = $this->_getModel('shipping/rate_result');
        $noRate = true;
        $freeMethods = explode(',', $this->getConfigData('free_methods'));
        $activeMethods = explode(',', $this->getConfigData('active_methods'));
        foreach ($this->getAllowedMethods() as $mCode => $mName) {
            if (!in_array($mCode, $activeMethods)) {
                continue;
            }

            $request->setMethodCode($mCode);
            $rate = $this->getRate($request);
            if (!empty($rate) && $rate['price'] >= 0) {
                $method = $this->_getModel('shipping/rate_result_method');

                $method->setCarrier($this->getCarrierCode());
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod($mCode);
                $method->setMethodTitle($this->getConfigData($mCode . '_name'));

                if (in_array($mCode, $freeMethods) &&
                    ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty))
                ) {
                    $shippingPrice = 0;
                } else {
                    $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
                }

                $method->setPrice($shippingPrice);
                $method->setCost($rate['cost']);

                $result->append($method);
                $noRate = false;
            }
        }

        if ($noRate) {
            $error = $this->_getModel('shipping/rate_result_error');
            $error->setCarrier($this->getCarrierCode());
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        $result = array();
        $allowedMethods = Mage::getModel('pShipping/source_shippingType')->getAllowedMethods();
        foreach ($allowedMethods as $mCode => $mName) {
            $result[$mCode] = $this->getConfigData($mCode . '_name');
        }

        return $result;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $tracking = explode('_', $tracking, 2);
        if (count($tracking) == 2) {
            /** @var Webtex_FbaOrder_Model_Order $fulfillmentOrder */
            $fulfillmentOrder = Mage::getModel('wford/order')->load($tracking[0]);
            $tracking = $tracking[1];
            if ($fulfillmentOrder && $fulfillmentOrder->getId()) {
                $tracks = $fulfillmentOrder->getTracks();
                if (isset($tracks[$tracking])) {
                    $fTrack = $tracks[$tracking];
                    $summary = "";
                    if ($fTrack->isSetEstimatedArrivalDate()) {
                        $summary .= "Estimate Arrival Date: " . $fTrack->getEstimatedArrivalDate() . "\n";
                    }
                    if ($fTrack->isSetCarrierPhoneNumber()) {
                        $summary .= "Carrier Phone Number: " . $fTrack->getCarrierPhoneNumber();
                    }
                    $summary = rtrim($summary, "\n");
                    if (!empty($summary)) {
                        $track->setTrackSummary($summary);
                    }
                    if ($fTrack->isSetCarrierCode()) {
                        $track->setCarrierCode($fTrack->getCarrierCode());
                    }
                    if ($fTrack->isSetTrackingNumber()) {
                        $track->setTrackingNumber($fTrack->getTrackingNumber());
                    }
                    if ($fTrack->isSetCarrierURL()) {
                        $track->setUrl($fTrack->getCarrierURL());
                    }
                    if ($fTrack->isSetCurrentStatus()) {
                        $track->setStatus($fTrack->getCurrentStatus());
                    }
                    return $track;
                }
            }
        } else {
            $tracking = $tracking[0];
        }
        $track->setTracking($tracking);

        return $track;
    }
}
