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
use \Magento\Shipping\Model\Rate\Result;
use \Magento\Store\Model\StoreManagerInterface;

class RateResult extends Result
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $_hlp;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $configScopeConfigInterface,
        \Unirgy\Dropship\Helper\Data $udropshipHelper
    )
    {
        $this->scopeConfig = $configScopeConfigInterface;
        $this->_hlp = $udropshipHelper;

        parent::__construct($storeManager);
    }

    public function sortRatesByPriority ()
    {
        if (!is_array($this->_rates) || !count($this->_rates)) {
            return $this;
        }
        /* @var $rate Result\Method */
        foreach ($this->_rates as $i => $rate) {
            $cmpPrice = $rate->hasBeforeExtPrice() ? $rate->getBeforeExtPrice() : $rate->getPrice();
            $tmp[$i] = 100*$rate->getPriority()+$cmpPrice+(int)$rate->getIsExtraCharge();
        }

        natsort($tmp);

        foreach ($tmp as $i => $price) {
            $result[] = $this->_rates[$i];
        }

        $this->reset();
        $this->_rates = $result;
        return $this;
    }
    public function sortRatesByPrice()
    {
        if ($this->_hlp->getScopeFlag('udropship/customer/allow_shipping_extra_charge')) {
            $this->sortRatesByPriority();
        } else {
            parent::sortRatesByPrice();
        }
        return $this;
    }
}