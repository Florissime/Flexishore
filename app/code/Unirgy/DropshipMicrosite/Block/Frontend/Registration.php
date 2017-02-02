<?php

namespace Unirgy\DropshipMicrosite\Block\Frontend;

use Magento\Directory\Block\Data as BlockData;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Profiler;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Model\VendorFactory;
use Magento\Framework\App\ObjectManager;

class Registration extends BlockData
{
    /**
     * @var VendorFactory
     */
    protected $_modelVendorFactory;

    protected $_formFactory;

    public function __construct(
        Context $context,
        HelperData $directoryHelper, 
        EncoderInterface $jsonEncoder, 
        Config $configCacheType, 
        CollectionFactory $regionCollectionFactory, 
        CountryCollectionFactory $countryCollectionFactory, 
        VendorFactory $modelVendorFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_modelVendorFactory = $modelVendorFactory;
        $this->_formFactory = $formFactory;

        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $data);
    }

    public function getCountryHtmlSelect($defValue=null, $name='country_id', $id='country', $title='Country')
    {
        Profiler::start('TEST: '.__METHOD__);
        $_isQuickRegister = $this->_scopeConfig->getValue('udropship/microsite/allow_quick_register', ScopeInterface::SCOPE_STORE);
        if (is_null($defValue)) {
            $defValue = $_isQuickRegister ? '' : $this->getCountryId();
        }
        $cacheKey = 'DIRECTORY_COUNTRY_SELECT_STORE_'.$this->_storeManager->getStore()->getCode();
        if ($this->_cacheState->isEnabled('config') && $cache = $this->_cache->load($cacheKey)) {
            $options = unserialize($cache);
        } else {
            $options = $this->getCountryCollection()->toOptionArray();
            if ($this->_cacheState->isEnabled('config')) {
                $this->_cache->save(serialize($options), $cacheKey, ['config']);
            }
        }
        $html = $this->_formFactory->create()->addField($id, 'select', [
            'name' => $name,
            'title' => __($title),
            'class' => $_isQuickRegister ? '' : 'validate-select',
            'value' => $defValue,
            'values' => $options
        ])->getHtml();

        Profiler::stop('TEST: '.__METHOD__);
        return $html;
    }
    protected $_tplVendor;
    protected function _initTplVendor()
    {
        if (null === $this->_tplVendor) {
            $this->_tplVendor = $this->_modelVendorFactory->create()->load($this->_scopeConfig->getValue('udropship/microsite/template_vendor', ScopeInterface::SCOPE_STORE));
        }
        return $this;
    }
    public function getDefPreferedCarrier()
    {
        $this->_initTplVendor();
        return $this->_tplVendor->getCarrierCode();
    }
}