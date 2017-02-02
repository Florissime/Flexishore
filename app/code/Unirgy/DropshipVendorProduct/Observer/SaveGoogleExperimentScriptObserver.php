<?php

namespace Unirgy\DropshipVendorProduct\Observer;
use Magento\Framework\Event\Observer;

class SaveGoogleExperimentScriptObserver extends \Magento\GoogleOptimizer\Observer\Product\SaveGoogleExperimentScriptObserver
{
    public function execute(Observer $observer)
    {
        try {
            parent::execute($observer);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('\Psr\Log\LoggerInterface')->error($e);
        }
    }
}