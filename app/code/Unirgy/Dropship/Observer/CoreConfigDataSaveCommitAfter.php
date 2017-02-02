<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CoreConfigDataSaveCommitAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        return;
        $configData = $observer->getEvent()->getConfigData();
        $paths = $this->getRuntimeProductAttributesConfigPaths();
        $addFilterable  = $this->_productFlat->isAddFilterableAttributes();
        $reindex = false;
        foreach ($paths as $path) {
            $path = str_replace('-', '/', $path);
            if ($configData->getPath() == $path
                && $configData->isValueChanged()
                && $configData->getValue()
                && ($attribute = $this->_hlp->getProductAttribute($configData->getValue()))
                && !(($attribute->getData('backend_type') == 'static')
                    || ($addFilterable && $attribute->getData('is_filterable') > 0)
                    || ($attribute->getData('used_in_product_listing') == 1)
                    || ($attribute->getData('is_used_for_promo_rules') == 1)
                    || ($attribute->getData('used_for_sort_by') == 1))
            ) {
                $reindex = true;
                break;
            }
        }
        if ($reindex && $this->_productFlat->isBuilt()) {
            $this->_modelIndexer->getProcessByCode('catalog_product_flat')->changeStatus(
                Process::STATUS_REQUIRE_REINDEX);
        }
    }
}
