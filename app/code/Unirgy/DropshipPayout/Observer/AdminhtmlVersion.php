<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AdminhtmlVersion extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $this->_hlp->addAdminhtmlVersion('Unirgy_DropshipPayout');
    }
}
