<?php

namespace Unirgy\Dropship\Cron;

class CollectTracking extends AbstractCron
{
    public function execute()
    {
        $statusFilter = array(
            \Unirgy\Dropship\Model\Source::TRACK_STATUS_PENDING,
            \Unirgy\Dropship\Model\Source::TRACK_STATUS_READY,
            \Unirgy\Dropship\Model\Source::TRACK_STATUS_SHIPPED
        );

            $res  = $this->_hlp->rHlp();
            $conn = $res->getConnection();

            $sIdsSel = $conn->select()->distinct()
                ->from($res->getTableName('sales_shipment_track'), array('parent_id'))
                ->where('udropship_status in (?)', $statusFilter)
                ->where('next_check<=?', $this->_hlp->now())
                ->limit(50);
            $sIds = $conn->fetchCol($sIdsSel);

        if (!empty($sIds)) {
            $tracks = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Track')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', array('in'=>$statusFilter))
                ->addAttributeToFilter('parent_id', array('in'=>$sIds))
                ->addAttributeToSort('parent_id')
            ;

            try {
                $this->_hlp->collectTracking($tracks);
            } catch (\Exception $e) {
                $tracksByStore = array();
                /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
                foreach ($tracks as $track) {
                    $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
                }
                foreach ($tracksByStore as $sId => $_tracks) {
                    $this->_errHlp->sendPollTrackingFailedNotification($_tracks, "$e", $sId);
                }
            }
        }

        if (0<$this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit')) {
            $limit = date('Y-m-d H:i:s', time()-24*60*60*$this->_hlp->getScopeConfig('udropship/error_notifications/poll_tracking_limit'));

            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $tracks */
            $tracks = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Track')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('udropship_status', 'P')
                ->addAttributeToFilter('created_at', array('datetime'=>true, 'to'=>$limit))
                ->setPageSize(50)
            ;
            $tracksByStore = array();
            /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
            foreach ($tracks as $track) {
                $cCode = $track->getCarrierCode();
                if (!$cCode) {
                    continue;
                }
                $vId = $track->getShipment()->getUdropshipVendor();
                $v = $this->_hlp->getVendor($vId);
                if (!$v->getTrackApi($cCode)) {
                    continue;
                }
                $tracksByStore[$track->getShipment()->getOrder()->getStoreId()][] = $track;
            }
            foreach ($tracksByStore as $sId => $_tracks) {
                $this->_errHlp->sendPollTrackingLimitExceededNotification($_tracks, $sId);
            }
        }
    }
}