<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class CatalogProductPrepareSave extends AbstractObserver implements ObserverInterface
{
    /**
    * Set current vendor ID for saved product when logged in as a vendor
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $vendor = $this->_getVendor();
        if ($product && $vendor) {
            $product->setUdropshipVendor($vendor->getId());

            $staging = $this->scopeConfig->getValue('udropship/microsite/staging_website', ScopeInterface::SCOPE_STORE);
            if ($staging || ($lw = $vendor->getLimitWebsites())) {
                $newWebsiteIds = $product->getWebsiteIds();
                $product->unsWebsiteIds();
                $websiteIds = $product->getWebsiteIds();
                if (!$staging) {
                    $websiteIds = array_diff($websiteIds, (is_array($lw) ? $lw : explode(',', $lw)));
                }
                $product->setWebsiteIds(array_unique(array_merge($websiteIds, $newWebsiteIds)));
            }
            if ($vendor->getIsLimitCategories()) {
                $newCatIds = $product->getCategoryIds();
                $product->unsCategoryIds();
                $catIds = $product->getCategoryIds();
                $lc = explode(',', implode(',', (array)$vendor->getLimitCategories()));
                if ($vendor->getIsLimitCategories() == 1) {
                    $catIds = array_diff($catIds, $lc);
                    $product->setCategoryIds(array_unique(array_merge($catIds, $newCatIds)));
                } elseif ($vendor->getIsLimitCategories() == 2) {
                    $catIds = array_intersect($catIds, $lc);
                    $product->setCategoryIds(array_unique(array_merge($catIds, $newCatIds)));
                } else {
                    $product->setCategoryIds($newCatIds);
                }
            }

            $hideFields = explode(',', $this->scopeConfig->getValue('udropship/microsite/hide_product_attributes', ScopeInterface::SCOPE_STORE));
            if (in_array('visibility', $hideFields) && !$product->hasData('visibility')) {
                $product->setData('visibility', Visibility::VISIBILITY_BOTH);
            }
            if (in_array('status', $hideFields) && !$product->hasData('status')) {
                $product->setData('status', Status::STATUS_DISABLED);
            }
        }
    }
}
