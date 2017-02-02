<?php

namespace Unirgy\Dropship\Plugin;

class OrderShipment
{
    public function beforeGetComments(\Magento\Sales\Model\Order\Shipment $subject)
    {
        if (!$subject->getUdInGetComments()) {
            $subject->setUdInGetComments(true);
            $subject->getCommentsCollection();
        }
        $subject->unsUdInGetComments();
    }
}