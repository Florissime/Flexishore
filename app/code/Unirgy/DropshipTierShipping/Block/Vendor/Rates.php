<?php

namespace Unirgy\DropshipTierShipping\Block\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Unirgy\Dropship\Model\Session;
use Unirgy\Dropship\Model\Vendor;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\Vendor
 */
class Rates extends Template
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Session
     */
    protected $_vendorSession;

    /**
     * Rates constructor.
     * @param Context $context
     * @param HelperData $helperData
     * @param Session $vendorSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Session $vendorSession,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_helperData = $helperData;
        $this->_vendorSession = $vendorSession;
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getTopCategories()
    {
        return $this->_helperData->getTopCategories();
    }

    /**
     * @return array
     */
    public function getTiershipRates()
    {
        $value = $this->getVendor()->getTiershipRates();
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
    public function getGlobalTierShipConfig()
    {
        $value = $this->_scopeConfig->getValue('carriers/udtiership/rates', ScopeInterface::SCOPE_STORE);
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
     * @return Vendor
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
            case 'handling':
                $title = __('Tier handling fee');
                break;
        }
        return $title;
    }

    /**
     * @return bool
     */
    public function isShowAdditionalColumn()
    {
        return $this->_helperData->useAdditional($this->getStore());
    }

    /**
     * @return bool
     */
    public function isShowHandlingColumn()
    {
        return $this->_helperData->useHandling($this->getStore());
    }

    /**
     * @return HelperData
     */
    function getHelper()
    {
        return $this->_helperData;
    }
    
    /*
     * if ($hasShipClass = \Magento\Framework\App\ObjectManager::getInstance()->get('Unirgy\Dropship\Helper\Data')->isModuleActive('udshipclass')) {
    $vShipClass = \Magento\Framework\App\ObjectManager::getInstance()->get('Unirgy\DropshipShippingClass\Model\Source')->setPath('vendor_ship_class')->toOptionHash();
    $cShipClass = \Magento\Framework\App\ObjectManager::getInstance()->get('Unirgy\DropshipShippingClass\Model\Source')->setPath('customer_ship_class')->toOptionHash();
    $vscId = \Magento\Framework\App\ObjectManager::getInstance()->get('Unirgy\DropshipShippingClass\Helper\Data')->getVendorShipClass($vendor);
}
    $rateBlock = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\View\Layout')->createBlock('Unirgy\DropshipTierShipping\Block\Vendor\RateSingle');

     */
}
