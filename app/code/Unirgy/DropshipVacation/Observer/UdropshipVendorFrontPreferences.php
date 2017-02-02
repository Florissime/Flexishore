<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipVendorFrontPreferences extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $data = $observer->getEvent()->getData();
        $data['fieldsets']['account']['fields']['vacation_mode'] = [
            'position' => 11,
            'name' => 'vacation_mode',
            'type' => 'select',
            'label' => 'Vacation Mode',
            'options' => $this->_hlp->getObj('\Unirgy\DropshipVacation\Model\Source')->setPath('vacation_mode')->toOptionArray(),
        ];
        $data['fieldsets']['account']['fields']['vacation_end'] = [
            'position' => 12,
            'name' => 'vacation_end',
            'type' => 'date',
            'label' => 'Vacation Ends At',
        ];
    }
}
