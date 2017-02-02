<?php

namespace Unirgy\Dropship\Controller\Vendor;

use \Unirgy\Dropship\Model\Source;

class UpdateShipmentsStatus extends AbstractVendor
{
    public function execute()
    {
        $hlp = $this->_hlp;
        try {
            $shipments = $this->getVendorShipmentCollection();
            $status = $this->getRequest()->getParam('update_status');

            $statusShipped = Source::SHIPMENT_STATUS_SHIPPED;
            $statusDelivered = Source::SHIPMENT_STATUS_DELIVERED;

            if (!$shipments->getSize()) {
                throw new \Exception(__('No shipments found for these criteria'));
            }
            if (is_null($status) || $status==='') {
                throw new \Exception(__('No status selected'));
            }

            $shipmentStatuses = false;
            if ($this->_hlp->getScopeConfig('udropship/vendor/is_restrict_shipment_status')) {
                $shipmentStatuses = $this->_hlp->getScopeConfig('udropship/vendor/restrict_shipment_status');
                if (!is_array($shipmentStatuses)) {
                    $shipmentStatuses = explode(',', $shipmentStatuses);
                }
            }
            foreach ($shipments as $shipment) {
                if (!$shipmentStatuses || (in_array($shipment->getUdropshipStatus(), $shipmentStatuses) && in_array($status, $shipmentStatuses))) {
                    if ($status==$statusShipped || $status==$statusDelivered) {
                        $tracks = $shipment->getAllTracks();
                        if (count($tracks)) {
                            foreach ($tracks as $track) {
                                $hlp->processTrackStatus($track, true, true);
                            }
                        } else {
                            $hlp->completeShipment($shipment, true, $status==$statusDelivered);
                            $hlp->completeOrderIfShipped($shipment, true);
                            $hlp->completeUdpoIfShipped($shipment, true);
                        }
                    }
                    $shipment->setUdropshipStatus($status)->save();
                }
            }
            $this->messageManager->addSuccess(__('Shipment status has been updated for the selected shipments'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultRedirectFactory->create()->setPath('udropship/vendor/', array('_current'=>true, '_query'=>array('submit_action'=>'')));
    }
}
