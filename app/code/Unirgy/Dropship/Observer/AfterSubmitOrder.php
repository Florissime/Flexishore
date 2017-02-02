<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\ProtectedCode;
use \Unirgy\Dropship\Observer\AbstractObserver;

class AfterSubmitOrder extends AbstractObserver implements ObserverInterface
{
    /**
     * @var \Unirgy\Dropship\Helper\ProtectedCode\OrderSave
     */
    protected $_hlpPr;

    public function __construct(
        \Unirgy\Dropship\Helper\ProtectedCode\OrderSave $helperProtectedCode,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_hlpPr = $helperProtectedCode;

        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        $observer->getEvent()->getOrder()->setNoDropshipFlag(false);
        $this->_hlpPr->sales_order_save_after($observer);
    }
}
