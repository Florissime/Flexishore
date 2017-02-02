<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

abstract class AbstractObserver
{
    /**
     * @var HelperData
     */
    protected $_tsHlp;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        HelperData $helperData,
        StoreManagerInterface $storeManager)
    {
        $this->_tsHlp = $helperData;
        $this->_storeManager = $storeManager;

    }



    protected function _initConfigRewrites()
    {
        return;
        if (!$this->_tsHlp->isV2Rates()) return;
        Mage::getConfig()->setNode('global/models/udtiership/rewrite/carrier', 'Unirgy\DropshipTierShipping\Model\V2\Carrier');
        foreach ([
                     $this->_storeManager->getStore(),
                     $this->_storeManager->getStore(0),
                 ] as $store) {
            $store->setConfig('carriers/udtiership/udtiership/model', 'udtiership/v2_carrier');
            Mage::getConfig()->setNode('default/carriers/udtiership/model', 'udtiership/v2_carrier');
        }
    }

}