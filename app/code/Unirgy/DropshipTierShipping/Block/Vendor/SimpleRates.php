<?php

namespace Unirgy\DropshipTierShipping\Block\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Unirgy\Dropship\Model\Session;

/**
 * Class SimpleRates
 * @package Unirgy\DropshipTierShipping\Block\Vendor
 */
class SimpleRates extends Template
{
    /**
     * @var Session
     */
    protected $_vendorSession;

    /**
     * SimpleRates constructor.
     * @param Template\Context $context
     * @param Session $vendorSession
     * @param array $data
     */
    public function __construct(Template\Context $context, Session $vendorSession, array $data)
    {
        parent::__construct($context, $data);
        $this->_vendorSession = $vendorSession;
    }

    /**
     * @return array
     */
    public function getTiershipSimpleRates()
    {
        $value = $this->getVendor()->getTiershipSimpleRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     * @return mixed
     */
    public function getGlobalTierShipConfigSimple()
    {
        $value = $this->_scopeConfig->getValue('carriers/udtiership/simple_rates',
                                               ScopeInterface::SCOPE_STORE);
        if (is_string($value)) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|null
     */
    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    /**
     * @return \Unirgy\Dropship\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_vendorSession->getVendor();
    }

    /**
     * @param $subkeyColumns
     * @param $idx
     * @return string
     */
    public function getColumnTitle($subkeyColumns, $idx)
    {
        reset($subkeyColumns);
        $i = 0;
        while ($i++ != $idx) next($subkeyColumns);
        $title = '';
        $column = current($subkeyColumns);
        switch ($column[1]) {
            case 'cost':
                $title = __('Cost for the first item');
                break;
            case 'additional':
                $title = __('Additional item cost');
                break;
        }
        return $title;
    }

}
