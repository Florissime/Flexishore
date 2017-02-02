<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Observer\AbstractObserver;

class CoreBlockAbstractToHtmlBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Item
     */
    protected $_iHlp;

    public function __construct(
        Item $helperItem,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_iHlp = $helperItem;
        parent::__construct($context, $data);
    }

    public function execute(Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof \Magento\Sales\Block\Adminhtml\Order\View\Items
            && (($order = $block->getOrder())
                || $block->getParentBlock() && ($order = $block->getParentBlock()->getOrder()))
        ) {
            $vendors = $this->_hlp->src()->getVendors();
            foreach ($order->getAllItems() as $oItem) {
                if ($oItem->isDummy(true)) continue;
                if (($vId = $oItem->getUdropshipVendor())
                    && isset($vendors[$vId])
                ) {
                    $this->_iHlp->setVendorIdOption($oItem, $vId, true);
                }
            }
            $this->_iHlp->attachOrderItemPoInfo($order);
        }
    }
}
