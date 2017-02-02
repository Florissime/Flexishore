<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;

class ControllerActionLayoutLoadBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    public function __construct(HelperData $helperData, 
        ScopeConfigInterface $configScopeConfigInterface, 
        StoreManagerInterface $modelStoreManagerInterface, 
        ProductFactory $modelProductFactory, 
        DropshipVendorProductHelperData $dropshipVendorProductHelperData, 
        Registry $frameworkRegistry)
    {
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($helperData, $configScopeConfigInterface, $modelStoreManagerInterface, $modelProductFactory, $dropshipVendorProductHelperData);
    }

    public function execute(Observer $observer)
    {
        if ($observer->getAction()
            && in_array($observer->getAction()->getFullActionName(), ['catalog_product_view','checkout_cart_configure'])
        ) {
            if ($this->_scopeConfig->isSetFlag('udprod/general/use_product_zoom', ScopeInterface::SCOPE_STORE)) {
                $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_zoom');
                if ((($p = $this->_frameworkRegistry->registry('current_product'))
                    || ($p = $this->_frameworkRegistry->registry('product')))
                    && $p->getTypeId()=='configurable'
                ) {
                    $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_zoom_configurable');
                }
            } else {
                if ((($p = $this->_frameworkRegistry->registry('current_product'))
                        || ($p = $this->_frameworkRegistry->registry('product')))
                    && $p->getTypeId()=='configurable'
                ) {
                    if ($this->_scopeConfig->isSetFlag('udprod/general/use_configurable_preselect', ScopeInterface::SCOPE_STORE)) {
                        $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_configurable_preselect');
                    } else {
                        $observer->getAction()->getLayout()->getUpdate()->addHandle('_udprod_product_configurable');
                    }
                }
            }
        }
    }
}
