<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Observer\AbstractObserver;

class OrderCancelAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $hlp = $this->_hlp;
        foreach ($order->getShipmentsCollection() as $shipment) {
            if ($shipment->getUdCanCancel()) {
                $statusCanceled  = Source::SHIPMENT_STATUS_CANCELED;
                $statuses = $this->_hlp->src()->setPath('shipment_statuses')->toOptionHash();
                $hlp->processShipmentStatusSave($shipment, $statusCanceled);
                $commentText = __("ORDER WAS CANCELED: shipment status was changed to %1", $statuses[$statusCanceled]);
                $comment = $this->_hlp->createObj('\Magento\Sales\Model\Order\Shipment\Comment')
                    ->setComment($commentText)
                    ->setIsCustomerNotified(false)
                    ->setIsVendorNotified(true)
                    ->setIsVisibleToVendor(true)
                    ->setUdropshipStatus($statuses[$statusCanceled]);
                $shipment->addComment($comment);
                $this->_hlp->sendShipmentCommentNotificationEmail($shipment, $commentText);
            }
        }
    }
}
