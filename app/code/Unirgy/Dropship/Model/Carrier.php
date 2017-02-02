<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\Model\ResourceModel\Transaction;
use \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use \Magento\Shipping\Model\Carrier\AbstractCarrier;
use \Magento\Shipping\Model\Carrier\CarrierInterface;
use \Magento\Quote\Model\Quote\Address\RateRequest;
use \Magento\Shipping\Model\Rate\Result;
use \Psr\Log\LoggerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Helper\ProtectedCode;

class Carrier
    extends AbstractCarrier
    implements CarrierInterface
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ProtectedCode
     */
    protected $_hlpPr;

    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Item
     */
    protected $_iHlp;

    public function __construct(
        HelperData $helper,
        ProtectedCode $helperProtectedCode,
        Source $source,
        ManagerInterface $eventManager,
        Item $helperItem,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory, 
        LoggerInterface $logger, 
        array $data = []
    )
    {
        $this->_hlp = $helper;
        $this->_hlpPr = $helperProtectedCode;
        $this->_iHlp = $helperItem;
        $this->_src = $source;
        $this->_eventManager = $eventManager;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }


    protected $_code = 'udropship';

    protected $_methods = array();
    protected $_allowedMethods = array();

    protected $_rawRequest;

    /**
    * Collect and combine rates from vendor carriers
    *
    * @param RateRequest $request
    * @return Result|boolean
    */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $this->_rawRequest = $request;

        $hlp = $this->_hlp;
        $hlpd = $this->_hlpPr;

        $carrierNames = $this->_src->getCarriers();

        // get available dropship shipping methods
        $shipping = $hlp->getShippingMethods();

        foreach ($shipping as $s) {
            $s->setIsSkipped(false);
        }

        // prepare data
        $items = $request->getAllItems();

        try {
            $hlpd->prepareQuoteItems($items);
            $hlpd->fixQuoteItemsWeight($items);
        } catch (\Exception $e) {
            return false;
        }

        if (!$hlpd->getQuote()) {
            return false;
        }

        $quote = $hlpd->getQuote();
        $quoteWebsiteId = false;
        if ($quote->getStore() instanceof DataObject) {
            $quoteWebsiteId = $quote->getStore()->getWebsiteId();
        }
        $address = $hlpd->getQuote()->getShippingAddress();
        foreach ($items as $item) {
            if ($item->getAddress()) {
                $address = $item->getAddress();
            }
            break;
        }

        $this->_eventManager->dispatch('udropship_carrier_collect_before', array('request'=>$request, 'address'=>$address));

        $requests = $hlpd->getRequestsByVendor($items, $request);

        $evTransport = new DataObject(array('requests'=>$requests, 'orig_request'=>$request));
        $this->_eventManager->dispatch('udropship_carrier_process_vendor_requests', array('transport'=>$evTransport, 'address'=>$address));
        $requests = $evTransport->getRequests();

        if ($quote->getUdropshipCarrierError()) {
            /* @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error*/
            $error = $this->_hlp->createObj('\Magento\Quote\Model\Quote\Address\RateResult\Error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $errorMsg = $this->getConfigData('specificerrmsg');
            $error->setErrorMessage($quote->getUdropshipCarrierError() ? $quote->getUdropshipCarrierError() : $errorMsg);
            return $error;
        }
        
        $requestVendors = array_keys($requests);
        // build separate requests grouped by carrier and vendor
        $carriers = array();
        $numMethodsPerVendor = array();
        $firstVendorRequests = array();
        foreach ($requests as $vId=>$vRequests) {
            foreach ($vRequests as $cCode=>$r) {
                if (empty($firstVendorRequests[$vId])) {
                    $firstVendorRequests[$vId] = $r;
                }
                #$cCode = $r->getCarrierCode();
                $carriers[$cCode][$vId]['request'] = $r;
                $methods = $hlp->getVendor($vId)->getShippingMethods();
                foreach ($methods as $__m) {
                    foreach ($__m as $m) {
                        if (($s = $shipping->getItemById($m['shipping_id']))) {
                            $s->useProfile($hlp->getVendor($vId));
                            $carriers[$cCode][$vId]['methods'][$s->getShippingCode()] = $s->getSystemMethods($cCode);
                            $s->resetProfile();
                        }
                    }
                }
            }
            // skip methods that are not shared by ALL vendors
            $vendorMethods = $hlp->getVendor($vId)->getShippingMethods();
            foreach ($shipping as $s) {
                if (empty($vendorMethods[$s->getId()])) {
                    $s->setIsSkipped(true);
                } else {
                    $_isSkippedShipping = new DataObject(array('result'=>false));
                    $this->_eventManager->dispatch('udropship_vendor_shipping_check_skipped', array(
                        'shipping'=>$s,
                        'address'=>$address,
                        'vendor'=>$hlp->getVendor($vId),
                        'request'=>$firstVendorRequests[$vId],
                        'result'=>$_isSkippedShipping
                    ));
                    $s->setIsSkipped($_isSkippedShipping->getResult());
                }
                $sWebsites = $s->getWebsiteIds();
                if (!is_array($sWebsites)) {
                    $sWebsites = array($sWebsites);
                }
                $sWebsites = array_filter($sWebsites);
                if (!empty($sWebsites) && false !== $quoteWebsiteId && !in_array($quoteWebsiteId, $sWebsites)) {
                    $s->setIsSkipped(true);
                }
            }
        }

        // quote.udropship_shipping_details
        $details = array('version' => $hlp->getVersion());
        // vendors participating in the estimate
        $vendors = array();

        $errorAction = $hlpd->getStore()->getConfig('udropship/customer/estimate_error_action');

        // send actual requests and collect results
        foreach ($carriers as $cCode=>$requests) {
            $keys = array_keys($requests);
            foreach ($keys as $k) {
                $vendor = $hlp->getVendor($k);
                $systemMethods = $hlp->getMultiSystemShippingMethodsByProfile($vendor);
                $vMethods = $vendor->getShippingMethods();
                $result = $hlpd->collectVendorCarrierRates($requests[$k]['request']);
                if ($result===false) {
                    if ($errorAction=='fail') {
                        //return $hlpd->errorResult();
                        continue;
                    } elseif ($errorAction=='skip') {
                        continue;
                    }
                }

                $rates = $result->getAllRates();
                $keys1 = array_keys($rates);
                foreach ($keys1 as $k1) {
                    if ($rates[$k1] instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                        if ($errorAction=='fail') {
                            //return $hlpd->errorResult('udropship', $rates[$k1]->getErrorMessage());
                            continue 2;
                        } elseif ($errorAction=='skip') {
                            continue 2;
                        }
                    }
                    $wildcardUsed = false;
                    if (empty($systemMethods[$rates[$k1]->getCarrier()][$rates[$k1]->getMethod()])) {
                        if (!empty($systemMethods[$rates[$k1]->getCarrier()]['*'])) {
                            $wildcardUsed = true;
                        } else {
                            continue;
                        }
                    }

                    if ($wildcardUsed) {
                        $smArray = $systemMethods[$rates[$k1]->getCarrier()]['*'];
                    } else {
                        $smArray = $systemMethods[$rates[$k1]->getCarrier()][$rates[$k1]->getMethod()];
                    }

                    foreach ($smArray as $s) {
                        $s->useProfile($vendor);
                        if ($s->getIsSkipped() || !isset($vMethods[$s->getId()])) {
                            continue;
                        }
                    foreach ($vMethods[$s->getId()] as $vMethod) {
                        $vendorCode = $vendor->getCarrierCode();
                        if ($requests[$k]['request']->getForcedCarrierFlag()) {
                            $ecCode = $ocCode = $rates[$k1]->getCarrier();
                        } else {
                            $ecCode = !empty($vMethod['est_carrier_code'])
                                ? $vMethod['est_carrier_code']
                                : (!empty($vMethod['carrier_code']) ? $vMethod['carrier_code'] : $vendorCode);
                            $ocCode = !empty($vMethod['carrier_code']) ? $vMethod['carrier_code'] : $vendorCode;
                        }
                        $oldEstCode = null;
                        if (!empty($details['methods'][$s->getShippingCode()]['vendors'][$k]['est_code'])) {
                            list($oldEstCode, ) = explode('_', $details['methods'][$s->getShippingCode()]['vendors'][$k]['est_code'], 2);
                        }
                        if ($ecCode!=$rates[$k1]->getCarrier()) {
                            if ($vendor->getUseRatesFallback()
                                && !$this->_hlp->isUdsprofileActive()
                            ) {
                                if ($oldEstCode==$ecCode) {
                                    continue;
                                } elseif ($oldEstCode!=$ecCode && $ecCode==$rates[$k1]->getCarrier()) {
                                } elseif ($oldEstCode!=$ocCode && $ocCode==$rates[$k1]->getCarrier()) {
                                    $ecCode = $ocCode;
                                } elseif (!$oldEstCode && $vendorCode==$rates[$k1]->getCarrier()) {
                                    $ecCode = $vendorCode;
                                } else {
                                    continue;
                                }
                            } else {
                                continue;
                            }
                        }
                        if ('**estimate**' == $ocCode) {
                            $ocCode = $ecCode;
                        }
                        if ($wildcardUsed && $ecCode!=$ocCode) {
                            continue;
                        }

                        if ($this->_hlp->isUdsprofileActive()) {
                            $codeToCompare = $vMethod['carrier_code'].'_'.$vMethod['method_code'];
                            if (!empty($vMethod['est_use_custom'])) {
                                $codeToCompare = $vMethod['est_carrier_code'].'_'.$vMethod['est_method_code'];
                            }
                            if ($codeToCompare!=$rates[$k1]->getCarrier().'_'.$rates[$k1]->getMethod()) {
                                continue;
                            }
                        }

                        $rates[$k1]->setUdsIsSkip(false);
                        $this->_eventManager->dispatch('udropship_process_vendor_carrier_single_rate_result', array(
                            'vendor_method'=>$vMethod,
                            'udmethod'=>$s,
                            'address'=>$address,
                            'vendor'=>$vendor,
                            'request'=>$requests[$k]['request'],
                            'rate'=>$rates[$k1],
                        ));

                        if ($rates[$k1]->getUdsIsSkip()) {
                            continue;
                        }

                        $shipPrice = $this->getUdRatePrice($rates[$k1], $requests[$k]['request'], $s);
                        $shipCost = $rates[$k1]->getCost();

                        $detail = array(
                            'cost' => sprintf('%.4f', $shipCost),
                            'price' => sprintf('%.4f', $shipPrice),
                            'cost_excl' => sprintf('%.4f', $this->getShippingPrice($shipCost, $vendor, $address, 'base')),
                            'cost_incl' => sprintf('%.4f', $this->getShippingPrice($shipCost, $vendor, $address, 'incl')),
                            'price_excl' => sprintf('%.4f', $this->getShippingPrice($shipPrice, $vendor, $address, 'base')),
                            'price_incl' => sprintf('%.4f', $this->getShippingPrice($shipPrice, $vendor, $address, 'incl')),
                            'cost_tax' => sprintf('%.4f', $this->getShippingPrice($shipCost, $vendor, $address, 'tax')),
                            'tax' => sprintf('%.4f', $this->getShippingPrice($shipPrice, $vendor, $address, 'tax')),
                            'est_code' => $rates[$k1]->getCarrier().'_'.$rates[$k1]->getMethod(),
                            'est_carrier_title' => $rates[$k1]->getCarrierTitle(),
                            'est_method_title' => $rates[$k1]->getMethodTitle(),
                            'package_weight'=>$requests[$k]['request']->getPackageWeight(),
                            'package_value'=>$requests[$k]['request']->getPackageValue(),
                            'days_in_transit'=>$s->getDaysInTransit()
                        );
                        if ($this->_hlp->isUdsprofileActive()) {
                            $detail['sort_order'] = $vMethod['sort_order'];
                        }
                        $detail['is_free_shipping'] = (int)$this->isFwFreeShipping($rates[$k1], $requests[$k]['request'], $s);
                        if ($ecCode==$ocCode) {
                            $detail['code'] = $detail['est_code'];
                            $detail['carrier_title'] = $detail['est_carrier_title'];
                            $detail['method_title'] = $detail['est_method_title'];
                        } else {
                            $ocMethod = $s->getSystemMethods($ocCode);
                            if ($this->_hlp->isUdsprofileActive()) {
                                $ocMethod = $vMethod['method_code'];
                            }
                            if (empty($ocMethod)) {
                                continue;
                            }
                            $methodNames = $hlp->getCarrierMethods($ocCode);
                            $detail['code'] = $ocCode.'_'.$ocMethod;
                            $detail['carrier_title'] = $carrierNames[$ocCode];
                            $detail['method_title'] = $methodNames[$ocMethod];
                        }
                        $vendors[$k] = 1;
                        $scKey = !$wildcardUsed ? $s->getShippingCode() : $s->getShippingCode().'___'.$detail['code'];
                        $details['methods'][$scKey]['id'] = $s->getShippingId();
                        if ($this->_hlp->isUdsprofileActive()
                            && !empty($details['methods'][$scKey]['vendors'][$k])
                            && $details['methods'][$scKey]['vendors'][$k]['sort_order']<$detail['sort_order']
                        ) {
                            continue;
                        }
                        if (($curUdpoSeqNumber = $requests[$k]['request']->getUdpoSeqNumber())
                            && !empty($details['methods'][$scKey]['vendors'][$k])
                        ) {
                            $snByVendor = $address->getSeqNumbersByVendor();
                            $snByVendor[$k][$curUdpoSeqNumber][$scKey] = true;
                            $address->setSeqNumbersByVendor($snByVendor);
                            $ratesBySeqNumber = @$details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'];
                            if (!is_array($ratesBySeqNumber)) {
                                $ratesBySeqNumber = array();
                            }
                            if (($oldRateBySeqNumber = @$ratesBySeqNumber[$curUdpoSeqNumber])) {
                                $details['methods'][$scKey]['vendors'][$k]['cost'] -= $oldRateBySeqNumber['cost'];
                                $details['methods'][$scKey]['vendors'][$k]['price'] -= $oldRateBySeqNumber['price'];
                                $details['methods'][$scKey]['vendors'][$k]['cost_excl'] -= $oldRateBySeqNumber['cost_excl'];
                                $details['methods'][$scKey]['vendors'][$k]['cost_incl'] -= $oldRateBySeqNumber['cost_incl'];
                                $details['methods'][$scKey]['vendors'][$k]['price_excl'] -= $oldRateBySeqNumber['price_excl'];
                                $details['methods'][$scKey]['vendors'][$k]['price_incl'] -= $oldRateBySeqNumber['price_incl'];
                                $details['methods'][$scKey]['vendors'][$k]['cost_tax'] -= $oldRateBySeqNumber['cost_tax'];
                                $details['methods'][$scKey]['vendors'][$k]['tax'] -= $oldRateBySeqNumber['tax'];
                            }
                            $ratesBySeqNumber[$curUdpoSeqNumber] = $detail;
                            //if (is_array($snByVendor) && $curUdpoSeqNumber>=max(array_keys($snByVendor))) {
                            $_resCost = $detail['cost'] + $details['methods'][$scKey]['vendors'][$k]['cost'];
                            $_resPrice = $detail['price'] + $details['methods'][$scKey]['vendors'][$k]['price'];
                            $_resCostExcl = $detail['cost_excl'] + $details['methods'][$scKey]['vendors'][$k]['cost_excl'];
                            $_resCostIncl = $detail['cost_incl'] + $details['methods'][$scKey]['vendors'][$k]['cost_incl'];
                            $_resPriceExcl = $detail['price_excl'] + $details['methods'][$scKey]['vendors'][$k]['price_excl'];
                            $_resPriceIncl = $detail['price_incl'] + $details['methods'][$scKey]['vendors'][$k]['price_incl'];
                            $_resCostTax = $detail['cost_tax'] + $details['methods'][$scKey]['vendors'][$k]['cost_tax'];
                            $_resTax = $detail['tax'] + $details['methods'][$scKey]['vendors'][$k]['tax'];
                            $details['methods'][$scKey]['vendors'][$k] = $detail;
                            $details['methods'][$scKey]['vendors'][$k]['cost'] = $_resCost;
                            $details['methods'][$scKey]['vendors'][$k]['price'] = $_resPrice;
                            $details['methods'][$scKey]['vendors'][$k]['cost_excl'] = $_resCostExcl;
                            $details['methods'][$scKey]['vendors'][$k]['cost_incl'] = $_resCostIncl;
                            $details['methods'][$scKey]['vendors'][$k]['price_excl'] = $_resPriceExcl;
                            $details['methods'][$scKey]['vendors'][$k]['price_incl'] = $_resPriceIncl;
                            $details['methods'][$scKey]['vendors'][$k]['cost_tax'] = $_resCostTax;
                            $details['methods'][$scKey]['vendors'][$k]['tax'] = $_resTax;
                            //}
                            $details['methods'][$scKey]['vendors'][$k]['is_free_shipping'] = $detail['is_free_shipping'];
                            $details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'] = $ratesBySeqNumber;
                        } else {
                            $details['methods'][$scKey]['vendors'][$k] = $detail;
                            if (!empty($curUdpoSeqNumber)) {
                                $snByVendor = $address->getSeqNumbersByVendor();
                                $snByVendor[$k][$curUdpoSeqNumber][$scKey] = true;
                                $address->setSeqNumbersByVendor($snByVendor);
                                $details['methods'][$scKey]['vendors'][$k]['rates_by_seq_number'] = array(
                                    $curUdpoSeqNumber => $detail
                                );
                            }
                        }
                        if ($wildcardUsed) {
                            $details['methods'][$scKey]['wildcard_code'] = $detail['code'];
                            $details['methods'][$scKey]['wildcard_carrier_title'] = $detail['carrier_title'];
                            $details['methods'][$scKey]['wildcard_method_title'] = $detail['method_title'];
                        }
                    }
                    }
                    foreach ($smArray as $s) {
                        $s->resetProfile();
                    }
                }
            }
        }
        $snByVendor = $address->getSeqNumbersByVendor();
        if (!empty($snByVendor) && is_array($snByVendor)) {
            $totalSnUsed = array();
            $methodsUsedBySn = array();
            foreach ($snByVendor as $vId => $snData) {
                foreach ($snData as $__sn => $snMethods) {
                    $totalSnUsed[$vId.'-'.$__sn] = 1;
                    foreach ($snMethods as $_snMethod => $_dummy) {
                        $methodsUsedBySn[$_snMethod][$vId.'-'.$__sn] = 1;
                    }
                }
            }
            $totalSnUsed = array_sum($totalSnUsed);
            foreach ($methodsUsedBySn as $_snMethod => $_snMethodTotals) {
                if (array_sum($_snMethodTotals)<$totalSnUsed) {
                    unset($details['methods'][$_snMethod]);
                }
            }
        }
        $address->setUdropshipShippingDetails(\Zend_Json::encode($details));
        /* @var \Magento\Framework\App\Request\Http $httpRequest */
        $httpRequest = $this->_hlp->getObj('\Magento\Framework\App\Request\Http');
        if ($httpRequest->getRouteName()=='googlecheckout') {
            $transaction = $this->_hlp->transactionFactory()->create();
            $transaction->addObject($address);
            foreach ($request->getAllItems() as $item) {
                $transaction->addObject($item);
            }
            $transaction->save();
        }

        if (empty($vendors) || ($errorAction == 'fail' && count($vendors)<count($requestVendors))) {
            return $hlpd->errorResult('udropship');
        }

        $totalMethod = $this->_hlp->getScopeConfig('udropship/customer/estimate_total_method');

        // collect prices from details
        $totals = array();
        $numVendors = sizeof($vendors);
        $processedMethods = array();
        foreach ($details['methods'] as $mCode=>$method) {
            $sId = $method['id'];
            unset($method['id']);
            if (in_array($method, $processedMethods)) continue;
            $processedMethods[] = $method;
            $method['id'] = $sId;
            $s = $shipping->getItemById($method['id']);
            // skip not common methods
            if ($s->getIsSkipped() || sizeof($method['vendors'])<$numVendors) {
                continue;
            }
            if (empty($totals[$mCode])) {
                $totals[$mCode] = array(
                    'is_free_shipping' => 1,
                    'cost' => 0,
                    'price' => 0,
                    'cost_excl' => 0,
                    'cost_incl' => 0,
                    'price_excl' => 0,
                    'price_incl' => 0,
                    'cost_tax' => 0,
                    'tax' => 0,
                    'title' => $s->getStoreTitle($quote->getStoreId())
                );
                if (!empty($method['wildcard_code'])) {
                    $totals[$mCode]['title'] .= sprintf(' [%s - %s]', $method['wildcard_carrier_title'], $method['wildcard_method_title']);
                }
            }
            foreach ($method['vendors'] as $vId=>$rate) {
                $totals[$mCode]['is_free_shipping'] = @$totals[$mCode]['is_free_shipping'] && @$rate['is_free_shipping'];
                $totals[$mCode]['cost'] = $hlp->applyEstimateTotalCostMethod($totals[$mCode]['cost'], $rate['cost']);
                $totals[$mCode]['price'] = $hlp->applyEstimateTotalPriceMethod($totals[$mCode]['price'], $rate['price']);
                $totals[$mCode]['cost_excl'] = $hlp->applyEstimateTotalCostMethod($totals[$mCode]['cost_excl'], $rate['cost_excl']);
                $totals[$mCode]['cost_incl'] = $hlp->applyEstimateTotalCostMethod($totals[$mCode]['cost_incl'], $rate['cost_incl']);
                $totals[$mCode]['price_excl'] = $hlp->applyEstimateTotalPriceMethod($totals[$mCode]['price_excl'], $rate['price_excl']);
                $totals[$mCode]['price_incl'] = $hlp->applyEstimateTotalPriceMethod($totals[$mCode]['price_incl'], $rate['price_incl']);
                $totals[$mCode]['cost_tax'] = $hlp->applyEstimateTotalCostMethod($totals[$mCode]['cost_tax'], $rate['cost_tax']);
                $totals[$mCode]['tax'] = $hlp->applyEstimateTotalPriceMethod($totals[$mCode]['tax'], $rate['tax']);
            }
        }

        /* @var \Magento\Shipping\Model\Rate\Result $result*/
        $result = $this->_hlp->createObj('\Magento\Shipping\Model\Rate\Result');

        // flat rate customization
        /*
        $method->setCarrier('udropship');
        $method->setCarrierTitle($dropshipCarrier->getConfigData('title'));
        $method->setMethod('flatrate');
        $method->setMethodTitle('Flat Rate');
        $method->setCost(7.5);
        $method->setPrice(7.5);
        $result->append($method);
        */
        if (!empty($totals)) {
            foreach ($totals as $mCode=>$total) {
                /* @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method*/
                $method = $this->_hlp->createObj('\Magento\Quote\Model\Quote\Address\RateResult\Method');

                $method->setCarrier('udropship');
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod($mCode);
                $method->setMethodTitle($total['title']);

                $method->setCost($total['cost']);
                if (!empty($total['is_free_shipping'])) {
                    $method->setPrice(0);
                } else {
                $method->setPrice($this->getMethodPrice($total['price'], $mCode));
                }

                $result->append($method);
            }
        } else {
            /* @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error*/
            $error = $this->_hlp->createObj('\Magento\Quote\Model\Quote\Address\RateResult\Error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $errorMsg = $this->getConfigData('specificerrmsg');
            $error->setErrorMessage($errorMsg);
            return $error;
        }

        $this->_eventManager->dispatch('udropship_carrier_collect_after', array('request'=>$request, 'result'=>$result, 'address'=>$address, 'details'=>$details));

        return $result;
    }

    public function getShippingPrice($baseShipping, $vId, $address, $type)
    {
        return $this->_hlp->getShippingPrice($baseShipping, $vId, $address, $type);
    }

    public function getMethodPrice($cost, $method='')
    {
        $freeMethods = explode(',', $this->getConfigData('free_method'));
        $freeShippingSubtotal = $this->getConfigData('free_shipping_subtotal');
        if ($freeShippingSubtotal === null || $freeShippingSubtotal === '') {
            $freeShippingSubtotal = false;
        }
        if (in_array($method, $freeMethods)
            && $this->getConfigData('free_shipping_allowed')
            && $this->getConfigData('free_shipping_enable')
            && $freeShippingSubtotal!==false
            && $freeShippingSubtotal <= $this->_rawRequest->getBaseSubtotalInclTax())
        {
            $price = '0.00';
        } else {
            $price = $this->getFinalPriceWithHandlingFee($cost);
        }
        return $price;
    }

    public function getAllowedMethods()
    {
        if (empty($this->_allowedMethods)) {
            $shipping = $this->_getAllMethods();
            $methods = array();
            foreach ($shipping as $m) {
                $methods[$m->getShippingCode()] = $m->getShippingTitle();
            }
            $this->_allowedMethods = $methods;
        }
        return $this->_allowedMethods;
    }

    protected function _getAllMethods()
    {
        if (empty($this->_methods)) {
            $this->_methods = $this->_hlp->getShippingMethods()
                ->setOrder('days_in_transit', 'desc');
        }
        return $this->_methods;
    }

    public function getUseForAllProducts()
    {
        return true;
    }

    public function isRuleFreeshipping($request)
    {
        $isFreeshipping = true;
        foreach ($request->getAllItems() as $item) {
            if ($item->getFreeShipping()!==true && $item->getTotalQty()>$item->getFreeShipping()) {
                $isFreeshipping = false;
                break;
            }
        }
        $address = $this->_iHlp->getAddress($request->getAllItems());
        if ($address instanceof DataObject && $address->getFreeShipping() === true) {
            $isFreeshipping = true;
        }
        return $isFreeshipping;
    }

    protected $_udFreeMethods;
    public function getUdFreeMethods()
    {
        if ($this->_udFreeMethods===null) {
            $hlp = $this->_hlp;
            $hlpd = $this->_hlpPr;
            $shipping = $hlp->getShippingMethods();
            $freeMethods = explode(',', $this->_hlp->getScopeConfig('carriers/udropship/free_method', $hlpd->getStore()));
            if ($freeMethods) {
                $_freeMethods = array();
                foreach ($freeMethods as $freeMethod) {
                    if (is_numeric($freeMethod)) {
                        if ($shipping->getItemById($freeMethod)) {
                            $_freeMethods[] = $freeMethod;
                        }
                    } else {
                        if ($shipping->getItemByColumnValue('shipping_code', $freeMethod)) {
                            $_freeMethods[] = $freeMethod;
                        }
                    }
                    $_freeMethods[] = $freeMethod;
                }
                $freeMethods = $_freeMethods;
            }
            $this->_udFreeMethods = $freeMethods;
        }
        return $this->_udFreeMethods;
    }

    public function getUdRatePrice($rate, $request, $udMethod)
    {
        return $this->isFwFreeShipping($rate, $request, $udMethod) ? 0 : $rate->getPrice();
    }

    public function isFwFreeShipping($rate, $request, $udMethod)
    {
        $resFlag = false;
        $freeMethods = $this->getUdFreeMethods();
        if ($freeMethods
            && $this->getConfigData('free_shipping_allowed')
            && $this->getConfigData('freeweight_allowed')
            && $this->isRuleFreeshipping($request)
            && in_array($udMethod->getShippingCode(), $freeMethods)
        ) {
            $resFlag = true;
        }
        return $resFlag;
    }

}
