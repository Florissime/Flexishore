<?php

namespace Unirgy\DropshipTierCommission\Block\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierCommission\Block\Vendor
 */
class Rates extends Template
{
    /**
     * @var HelperData
     */
    protected $_tsHlp;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    /**
     * Rates constructor.
     * @param Context $context
     * @param HelperData $helperData
     * @param Session $dpSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $tiershipHelper,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        array $data = []
    ) {
        $this->_tsHlp = $tiershipHelper;
        $this->_hlp = $udropshipHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getTopCategories()
    {
        return $this->_tsHlp->getTopCategories();
    }

    /**
     * @return array
     */
    public function getTiercomRates()
    {
        $value = $this->getVendor()->getTiercomRates();
        if (is_string($value)) {
            $value = unserialize($value);
        }
        if (!is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     * @return array
     */
    public function getGlobalTierComConfig()
    {
        $value = $this->_scopeConfig->getValue('udropship/tiercom/rates', ScopeInterface::SCOPE_STORE);
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
        return $this->_hlp->session()->getVendor();
    }

    public function getHelper()
    {
        return $this->_tsHlp;
    }
}
