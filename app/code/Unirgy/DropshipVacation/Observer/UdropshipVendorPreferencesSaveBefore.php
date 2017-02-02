<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorPreferencesSaveBefore extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();
        $v = $data['vendor'];
        $p = $data['post_data'];
        foreach ([
            'vacation_mode', 'vacation_end'
        ] as $f) {
            $v->setData($f, @$p[$f]);
        }
    }
}
