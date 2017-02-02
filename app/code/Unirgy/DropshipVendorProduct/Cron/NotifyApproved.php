<?php

namespace Unirgy\DropshipVendorProduct\Cron;

class NotifyApproved extends AbstractCron
{
    public function execute()
    {
        $oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);
        $prods = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect(['sku', 'name', 'udropship_vendor', 'udprod_attributes_changed', 'udprod_cfg_simples_added', 'udprod_cfg_simples_removed'])
            ->addAttributeToFilter('status', \Unirgy\DropshipVendorProduct\Model\ProductStatus::STATUS_ENABLED)
            ->addAttributeToFilter('udprod_approved_notify', ['gt'=>0])
            ->addAttributeToFilter('udprod_approved_notified', 0)
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
            $this->_prodHlp->sendApprovedNotificationEmail($vProds, $v);
            $this->_prodHlp->sendApprovedAdminNotificationEmail($vProds, $v);
        }
        $this->_storeManager->setCurrentStore($oldStoreId);
    }
}