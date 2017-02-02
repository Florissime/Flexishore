<?php

namespace Unirgy\DropshipVendorProduct\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Model\ProductStatus;

class CatalogProductAttributeUpdateBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $data = $observer->getData();
        if (!empty($data['product_ids'])
            && isset($data['attributes_data']['status'])
            && in_array($data['attributes_data']['status'], [ProductStatus::STATUS_FIX, ProductStatus::STATUS_ENABLED])
        ) {
            $multiUpdateAttributes = [];
            $origProds = $this->_productFactory->create()->getCollection()->addIdFilter($data['product_ids']);
            $origProds->addAttributeToSelect(['status','udprod_pending_notify']);
            foreach ($origProds as $prod) {
                if (in_array($prod->getData('status'), [ProductStatus::STATUS_PENDING, ProductStatus::STATUS_FIX])
                    && $data['attributes_data']['status'] == ProductStatus::STATUS_ENABLED
                ) {
                    $multiUpdateAttributes[$prod->getId()] = [
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
                } elseif ($prod->getData('status') != ProductStatus::STATUS_FIX
                    && $data['attributes_data']['status'] == ProductStatus::STATUS_FIX
                ) {
                    $multiUpdateAttributes[$prod->getId()] = [
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
                        $multiUpdateAttributes[$prod->getId()]['udprod_fix_notify'] = $prod->getData('udprod_pending_notify');
                    }
                }
            }
            if (!empty($multiUpdateAttributes)) {
                $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\ProductHelper')->multiUpdateAttributes($multiUpdateAttributes, 0);
            }
        }
    }
}
