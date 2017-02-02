<?php

namespace Unirgy\DropshipVendorProduct\Cron;

class NotifyFix extends AbstractCron
{
    public function execute()
    {
        $oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);
        $prods = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect(['sku', 'name', 'udropship_vendor', 'udprod_attributes_changed', 'udprod_cfg_simples_added', 'udprod_cfg_simples_removed', 'udprod_fix_description'])
            ->addAttributeToFilter('status', \Unirgy\DropshipVendorProduct\Model\ProductStatus::STATUS_FIX)
            ->addAttributeToFilter('udprod_fix_notify', ['gt'=>0])
            ->addAttributeToFilter('udprod_fix_notified', 0)
        ;
        $this->prepareForNotification($prods);
        $prodByVendor = [];
        foreach ($prods as $prod) {
            if (($vId = $prod->getUdropshipVendor()) && ($v = $this->_hlp->getVendor($vId)) && $v->getId()) {
                $prodByVendor[$vId][$prod->getId()] = $prod;
            }
        }
        foreach ($prodByVendor as $vId=>$vProds) {
            $v = $this->_hlp->getVendor($vId);
            $this->_prodHlp->sendFixNotificationEmail($vProds, $v);
            $this->_prodHlp->sendFixAdminNotificationEmail($vProds, $v);
        }
        $this->_storeManager->setCurrentStore($oldStoreId);
    }
}