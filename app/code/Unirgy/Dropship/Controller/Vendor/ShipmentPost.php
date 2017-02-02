<?php

namespace Unirgy\Dropship\Controller\Vendor;

use \Magento\Sales\Model\Order\Shipment;
use \Magento\Sales\Model\Order\Shipment\Track;
use \Unirgy\Dropship\Model\Source;

class ShipmentPost extends AbstractVendor
{
    public function execute()
    {
        $hlp = $this->_hlp;
        $r = $this->getRequest();
        $id = $r->getParam('id');
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment')->load($id);
        $vendor = $hlp->getVendor($shipment->getUdropshipVendor());
        $session = $this->_getSession();

        if (!$shipment->getId()) {
            return;
        }

        try {
            $store = $shipment->getOrder()->getStore();

            $track = null;
            $highlight = array();

            $partial = $r->getParam('partial_availability');
            $partialQty = $r->getParam('partial_qty');

            $printLabel = $r->getParam('print_label');
            $number = $r->getParam('tracking_id');

            $carrier = $r->getParam('carrier');
            $carrierTitle = $r->getParam('carrier_title');

            $notifyOn = $this->_hlp->getScopeConfig('udropship/customer/notify_on', $store);
            $pollTracking = $this->_hlp->getScopeConfig('udropship/customer/poll_tracking', $store);
            $autoComplete = $this->_hlp->getScopeConfig('udropship/vendor/auto_shipment_complete', $store);

            $statusShipped = Source::SHIPMENT_STATUS_SHIPPED;
            $statusDelivered = Source::SHIPMENT_STATUS_DELIVERED;
            $statusCanceled = Source::SHIPMENT_STATUS_CANCELED;
            $statuses = $this->_hlp->src()->setPath('shipment_statuses')->toOptionHash();
            // if label was printed
            if ($printLabel) {
                $status = $r->getParam('is_shipped') ? $statusShipped : Source::SHIPMENT_STATUS_PARTIAL;
                $isShipped = $r->getParam('is_shipped') ? true : false;
            } else { // if status was set manually
                $status = $r->getParam('status');
                $isShipped = $status == $statusShipped || $status==$statusDelivered || $autoComplete && ($status==='' || is_null($status));
            }

            // if label to be printed
            if ($printLabel) {
                $data = array(
                    'weight'    => $r->getParam('weight'),
                    'value'     => $r->getParam('value'),
                    'length'    => $r->getParam('length'),
                    'width'     => $r->getParam('width'),
                    'height'    => $r->getParam('height'),
                    'reference' => $r->getParam('reference'),
                    'package_count' => $r->getParam('package_count'),
                );

                $extraLblInfo = $r->getParam('extra_label_info');
                $extraLblInfo = is_array($extraLblInfo) ? $extraLblInfo : array();
                $data = array_merge($data, $extraLblInfo);

                $oldUdropshipMethod = $shipment->getUdropshipMethod();
                $oldUdropshipMethodDesc = $shipment->getUdropshipMethodDescription();
                if ($r->getParam('use_method_code')) {
                    list($useCarrier, $useMethod) = explode('_', $r->getParam('use_method_code'), 2);
                    if (!empty($useCarrier) && !empty($useMethod)) {
                        $shipment->setUdropshipMethod($r->getParam('use_method_code'));
                        $carrierMethods = $this->_hlp->getCarrierMethods($useCarrier);
                        $shipment->setUdropshipMethodDescription(
                            $this->_hlp->getScopeConfig('carriers/'.$useCarrier.'/title', $shipment->getOrder()->getStoreId())
                            .' - '.$carrierMethods[$useMethod]
                        );
                    }
                }

                // generate label
                /** @var \Unirgy\Dropship\Model\Label\Batch $batch */
                $batch = $this->_hlp->createObj('\Unirgy\Dropship\Model\Label\Batch')
                    ->setVendor($this->_hlp->session()->getVendor())
                    ->processShipments(array($shipment), $data, array('mark_shipped'=>$isShipped));

                // if batch of 1 label is successfull
                if ($batch->getShipmentCnt()) {
                    $url = $this->_url->getUrl('udropship/vendor/reprintLabelBatch', array('batch_id'=>$batch->getId()));
                    $this->_registry->register('udropship_download_url', $url);

                    if (($track = $batch->getLastTrack())) {
                        $this->messageManager->addSuccess('Label was succesfully created');
                        $this->_hlp->addShipmentComment(
                            $shipment,
                            __('%1 printed label ID %2', $vendor->getVendorName(), $track->getNumber())
                        );
                        $shipment->save();
                        $highlight['tracking'] = true;
                    }
                    if ($r->getParam('use_method_code')) {
                        $shipment->setUdropshipMethod($oldUdropshipMethod);
                        $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                        $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                    }
                } else {
                    if ($batch->getErrors()) {
                        foreach ($batch->getErrors() as $error=>$cnt) {
                            $this->messageManager->addError(__($error, $cnt));
                        }
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
                    } else {
                        $this->messageManager->addError(__('No items are available for shipment'));
                        if ($r->getParam('use_method_code')) {
                            $shipment->setUdropshipMethod($oldUdropshipMethod);
                            $shipment->setUdropshipMethodDescription($oldUdropshipMethodDesc);
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method');
                            $shipment->getResource()->saveAttribute($shipment, 'udropship_method_description');
                        }
                    }
                }

            } elseif ($number) { // if tracking id was added manually
                $method = explode('_', $shipment->getUdropshipMethod(), 2);
                $title = $this->_hlp->getScopeConfig('carriers/'.$method[0].'/title', $store);
                $_carrier = $method[0];
                if (!empty($carrier) && !empty($carrierTitle)) {
                    $_carrier = $carrier;
                    $title = $carrierTitle;
                }
                $track = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Track')
                    ->setTrackNumber($number)
                    ->setCarrierCode($_carrier)
                    ->setTitle($title);

                $shipment->addTrack($track);

                $this->_hlp->processTrackStatus($track, true, $isShipped);

                $this->_hlp->addShipmentComment(
                    $shipment,
                    __('%1 added tracking ID %2', $vendor->getVendorName(), $number)
                );
                $shipment->save();
                $this->messageManager->addSuccess(__('Tracking ID has been added'));

                $highlight['tracking'] = true;
            }

            // if track was generated - for both label and manual tracking id
            /*
            if ($track) {
                // if poll tracking is enabled for the vendor
                if ($pollTracking && $vendor->getTrackApi()) {
                    $track->setUdropshipStatus(Source::TRACK_STATUS_PENDING);
                    $isShipped = false;
                } else { // otherwise process track
                    $track->setUdropshipStatus(Source::TRACK_STATUS_READY);
                    $this->_helperData->processTrackStatus($track, true, $isShipped);
                }
            */
            // if tracking id added manually and new status is not current status
            $shipmentStatuses = false;
            if ($this->_hlp->getScopeConfig('udropship/vendor/is_restrict_shipment_status')) {
                $shipmentStatuses = $this->_hlp->getScopeConfig('udropship/vendor/restrict_shipment_status');
                if (!is_array($shipmentStatuses)) {
                    $shipmentStatuses = explode(',', $shipmentStatuses);
                }
            }
            if (!$printLabel && !is_null($status) && $status!=='' && $status!=$shipment->getUdropshipStatus()
                && (!$shipmentStatuses || (in_array($shipment->getUdropshipStatus(), $shipmentStatuses) && in_array($status, $shipmentStatuses)))
            ) {
                $oldStatus = $shipment->getUdropshipStatus();
                if (($oldStatus==$statusShipped || $oldStatus==$statusDelivered)
                    && $status!=$statusShipped && $status!=$statusDelivered && $hlp->isUdpoActive()
                ) {
                    $this->_hlp->udpoHlp()->revertCompleteShipment($shipment, true);
                } elseif ($oldStatus==$statusCanceled && $hlp->isUdpoActive()) {
                    throw new \Exception(__('Canceled shipment cannot be reverted'));
                }
                $changedComment = __('%1 has changed the shipment status to %2', $vendor->getVendorName(), $statuses[$status]);
                $triedToChangeComment = __('%1 tried to change the shipment status to %2', $vendor->getVendorName(), $statuses[$status]);
                if ($status==$statusShipped || $status==$statusDelivered) {
                    $hlp->completeShipment($shipment, true, $status==$statusDelivered);
                    $hlp->completeOrderIfShipped($shipment, true);
                    $hlp->completeUdpoIfShipped($shipment, true);
                    $this->_hlp->addShipmentComment(
                        $shipment,
                        $changedComment
                    );
                } elseif ($status == $statusCanceled && $hlp->isUdpoActive()) {
                    if ($this->_hlp->udpoHlp()->cancelShipment($shipment, true)) {
                        $this->_hlp->addShipmentComment(
                            $shipment,
                            $changedComment
                        );
                        $this->_hlp->udpoHlp()->processPoStatusSave($this->_hlp->udpoHlp()->getShipmentPo($shipment), \Unirgy\DropshipPo\Model\Source::UDPO_STATUS_PARTIAL, true, $vendor);
                    } else {
                        $this->_hlp->addShipmentComment(
                            $shipment,
                            $triedToChangeComment
                        );
                    }
                } else {
                    $shipment->setUdropshipStatus($status)->save();
                    $this->_hlp->addShipmentComment(
                        $shipment,
                        $changedComment
                    );
                }
                $shipment->getCommentsCollection()->save();
                $this->messageManager->addSuccess(__('Shipment status has been changed'));
            }

            $comment = $r->getParam('comment');
            if ($comment || $partial=='inform' && $partialQty) {
                if ($partialQty) {
                    $comment .= "\n\nPartial Availability:\n";
                    foreach ($shipment->getAllItems() as $item) {
                        if (!array_key_exists($item->getId(), $partialQty) || '' === $partialQty[$item->getId()]) {
                            continue;
                        }
                        $comment .= __('%1 x [%2] %3', $partialQty[$item->getId()], $item->getName(), $item->getSku())."\n";
                    }
                }

                $this->_hlp->sendVendorComment($shipment, $comment);
                $this->messageManager->addSuccess(__('Your comment has been sent to store administrator'));

                $highlight['comment'] = true;
            }

            $deleteTrack = $r->getParam('delete_track');
            if ($deleteTrack) {
                /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
                $track = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Track')->load($deleteTrack);
                if ($track->getId()) {

                    try {
                        $labelModel = $this->_hlp->getLabelCarrierInstance($track->getCarrierCode())->setVendor($vendor);
                        try {
                            $labelModel->voidLabel($track);
                            $this->_hlp->addShipmentComment(
                                $shipment,
                                __('%1 voided tracking ID %2', $vendor->getVendorName(), $track->getNumber())
                            );
                            $this->messageManager->addSuccess(__('Track %1 was voided', $track->getNumber()));
                        } catch (\Exception $e) {
                            $this->_hlp->addShipmentComment(
                                $shipment,
                                __('%1 attempted to void tracking ID %2: %3', $vendor->getVendorName(), $track->getNumber(), $e->getMessage())
                            );
                            $this->messageManager->addSuccess(__('Problem voiding track %1: %2', $track->getNumber(), $e->getMessage()));
                        }
                    } catch (\Exception $e) {
                        // doesn't support voiding
                    }

                    $track->delete();
                    if ($track->getPackageCount()>1) {
                        foreach ($this->_hlp->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection')
                            ->addAttributeToFilter('master_tracking_id', $track->getMasterTrackingId())
                            as $_track
                        ) {
                            $_track->delete();
                        }
                    }
                    $this->_hlp->addShipmentComment(
                        $shipment,
                        __('%1 deleted tracking ID %2', $vendor->getVendorName(), $track->getNumber())
                    );
                    $shipment->getCommentsCollection()->save();
                    #$save = true;
                    $highlight['tracking'] = true;
                    $this->messageManager->addSuccess(__('Track %1 was deleted', $track->getNumber()));
                } else {
                    $this->messageManager->addError(__('Track %1 was not found', $track->getNumber()));
                }
            }

            $session->setHighlight($highlight);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this->_resultForwardFactory->create()->forward('shipmentInfo');
    }
}
