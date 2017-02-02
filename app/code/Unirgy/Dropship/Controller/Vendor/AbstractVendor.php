<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Controller\Vendor;

use \Unirgy\Dropship\Controller\VendorAbstract;
use \Unirgy\Dropship\Model\Source;
use \Magento\Store\Model\StoreManagerInterface;

abstract class AbstractVendor extends VendorAbstract
{
    protected function _preparePackingSlips($shipments)
    {
        $vendorId = $this->_getSession()->getId();
        $vendor = $this->_hlp->getVendor($vendorId);

        foreach ($shipments as $shipment) {
            if ($shipment->getUdropshipVendor()!=$vendorId) {
                throw new \Exception(__('You are not authorized to print this shipment'));
            }
        }

        if ($this->_hlp->getScopeConfig('udropship/vendor/ready_on_packingslip')) {
            foreach ($shipments as $shipment) {
                $this->_hlp->addShipmentComment(
                    $shipment,
                    __('%1 printed packing slip', $vendor->getVendorName())
                );
                if ($shipment->getUdropshipStatus()==Source::SHIPMENT_STATUS_PENDING) {
                    $shipment->setUdropshipStatus(Source::SHIPMENT_STATUS_READY);
                }
                $shipment->save();
            }
        }

        foreach ($shipments as $shipment) {
            $order = $shipment->getOrder();
            $order->setData('__orig_shipping_amount', $order->getShippingAmount());
            $order->setData('__orig_base_shipping_amount', $order->getBaseShippingAmount());
            $order->setShippingAmount($shipment->getShippingAmount());
            $order->setBaseShippingAmount($shipment->getBaseShippingAmount());
        }

        $theme = $this->_hlp->getScopeConfig('udropship/admin/interface_theme', 0);
        if (empty($theme)) {
            $theme = null;
        }
        $this->_hlp->setDesignStore(0, \Magento\Framework\App\Area::AREA_ADMINHTML, $theme);

        $pdf = $this->_hlp->getVendorShipmentsPdf($shipments);
        $filename = 'packingslip'.date('Y-m-d_H-i-s').'.pdf';

        foreach ($shipments as $shipment) {
            $order = $shipment->getOrder();
            $order->setShippingAmount($order->getData('__orig_shipping_amount'));
            $order->setBaseShippingAmount($order->getData('__orig_base_shipping_amount'));
        }

        $this->_hlp->sendDownload($filename, $pdf->render(), 'application/x-pdf');
    }


    public function getVendorShipmentCollection()
    {
        return $this->_hlp->getVendorShipmentCollection();
    }
}
