<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\Data;
use \Unirgy\Dropship\Helper\ProtectedCode;
use \Unirgy\Dropship\Observer\AbstractObserver;

use \Magento\Sales\Model\Order as ModelOrder;
use \Unirgy\Dropship\Model\Source;

class SalesOrderSaveCommitAfter extends AbstractObserver implements ObserverInterface
{
    /**
    \Unirgy\Dropship\Helper\ProtectedCode\OrderSave
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
        if (!$observer->getEvent()->getOrder()->getNoDropshipFlag()) {
            $this->_hlpPr->sales_order_save_after($observer);
        }
    }
}
