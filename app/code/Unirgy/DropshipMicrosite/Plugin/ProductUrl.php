<?php

namespace Unirgy\DropshipMicrosite\Plugin;

class ProductUrl
{
    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }
    public function aroundGetProductUrl(
        \Magento\Catalog\Model\Product\Url $subject,
        \Closure $proceed,
        $product, $useSid = null
    ) {
        $this->storeManager->getStore()->useVendorUrl(true);
        $pUrl =  $proceed($product, $useSid);
        $this->storeManager->getStore()->resetUseVendorUrl();
        return $pUrl;
    }
}