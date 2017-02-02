<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\App;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\DropshipVendorProduct\Model\ProductStatus;
use Unirgy\Dropship\Helper\Data as HelperData;

abstract class AbstractObserver
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var DropshipVendorProductHelperData
     */
    protected $_prodHlp;

    public function __construct(
        HelperData $udropshipHelper,
        ScopeConfigInterface $configScopeConfigInterface, 
        StoreManagerInterface $modelStoreManagerInterface, 
        ProductFactory $modelProductFactory, 
        DropshipVendorProductHelperData $vendorProductHelper
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_productFactory = $modelProductFactory;
        $this->_prodHlp = $vendorProductHelper;

    }

    protected function _initConfigRewrites()
    {
        foreach ([
            [
                'udprod_manage_stock'=>\Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK,
                'udprod_backorders'=>\Magento\CatalogInventory\Model\Configuration::XML_PATH_BACKORDERS,
            ],
            [
                'udprod_min_qty'=>\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_QTY,
                'udprod_min_sale_qty'=>\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_SALE_QTY,
                'udprod_max_sale_qty'=>\Magento\CatalogInventory\Model\Configuration::XML_PATH_MAX_SALE_QTY,
            ],
        ] as $isFloat=>$cfgKeyMap) {
            foreach ($cfgKeyMap as $vKey=>$cfgPath) {
                if ($isFloat) {
                    $this->_hlp->config()->setConfigData('vendor/fields/'.$vKey.'/default',
                        (float)$this->_scopeConfig->getValue($cfgPath, ScopeInterface::SCOPE_STORE));
                } else {
                    $this->_hlp->config()->setConfigData('vendor/fields/'.$vKey.'/default',
                        (int)$this->_scopeConfig->getValue($cfgPath, ScopeInterface::SCOPE_STORE));
                }
            }
        }
        $this->_hlp->config()->setConfigData('vendor/fields/udprod_manage_stock/default',
            (int)$this->_scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK, ScopeInterface::SCOPE_STORE));
        $this->_hlp->config()->setConfigData('vendor/fields/udprod_backorders/default',
            (int)$this->_scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_BACKORDERS, ScopeInterface::SCOPE_STORE));
        $this->_hlp->config()->setConfigData('vendor/fields/udprod_min_qty/default',
            (int)$this->_scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_QTY, ScopeInterface::SCOPE_STORE));
        $this->_hlp->config()->setConfigData('vendor/fields/udprod_min_sale_qty/default',
            (int)$this->_scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_SALE_QTY, ScopeInterface::SCOPE_STORE));
        $this->_hlp->config()->setConfigData('vendor/fields/udprod_max_sale_qty/default',
            (int)$this->_scopeConfig->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MAX_SALE_QTY, ScopeInterface::SCOPE_STORE));
    }

}