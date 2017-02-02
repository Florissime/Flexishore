<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipVendorProduct\Model\ProductStatus;

class CatalogProductSaveCommitAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $rowIdField = $this->_hlp->rowIdField();
        $prod = $observer->getProduct();
        if (in_array($prod->getOrigData('status'), [ProductStatus::STATUS_PENDING, ProductStatus::STATUS_FIX])
            && $prod->getData('status') == ProductStatus::STATUS_ENABLED
        ) {
            $multiUpdateAttributes[$prod->getData($rowIdField)] = [
                'udprod_fix_notify' => 0,
                'udprod_pending_notify' => 0,
                'udprod_approved_notify' => 1,
                'udprod_fix_notified' => 1,
                'udprod_pending_notified' => 1,
                'udprod_approved_notified' => 0,
                'udprod_fix_admin_notified' => 1,
                'udprod_pending_admin_notified' => 1,
                'udprod_approved_admin_notified' => 0,
                'udprod_attributes_changed' => '',
                'udprod_cfg_simples_added' => '',
                'udprod_cfg_simples_removed' => '',
                'udprod_fix_description' => '',
            ];
        } elseif ($prod->getOrigData('status') != ProductStatus::STATUS_FIX
            && $prod->getData('status') == ProductStatus::STATUS_FIX
        ) {
            $multiUpdateAttributes[$prod->getData($rowIdField)] = [
                'udprod_fix_notify' => 1,
                'udprod_pending_notify' => 0,
                'udprod_approved_notify' => 0,
                'udprod_fix_notified' => 0,
                'udprod_pending_notified' => 1,
                'udprod_approved_notified' => 1,
                'udprod_fix_admin_notified' => 0,
                'udprod_pending_admin_notified' => 1,
                'udprod_approved_admin_notified' => 1,
            ];
            if ($prod->getData('udprod_pending_notify')) {
                $multiUpdateAttributes[$prod->getData($rowIdField)]['udprod_fix_notify'] = $prod->getData('udprod_pending_notify');
            }
        }
        if (!empty($multiUpdateAttributes)) {
            $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\ProductHelper')->multiUpdateAttributes($multiUpdateAttributes, 0);
        }
    }
}
