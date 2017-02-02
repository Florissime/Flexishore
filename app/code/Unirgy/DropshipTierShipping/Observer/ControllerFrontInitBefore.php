<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ControllerFrontInitBefore
 * @package Unirgy\DropshipTierShipping\Observer
 */
class ControllerFrontInitBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->_initConfigRewrites();
    }
}
