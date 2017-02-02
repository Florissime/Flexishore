<?php

namespace Unirgy\Dropship\Observer;

use \Magento\Framework\DataObject;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Helper\ProtectedCode;
use \Unirgy\Dropship\Model\Stock\Availability;
use \Unirgy\Dropship\Observer\AbstractObserver;

class SalesQuoteItemQtySetAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Availability
     */
    protected $_stockAvailability;

    /**
     * @var ProtectedCode
     */
    protected $_hlpPr;

    /**
     * @var Item
     */
    protected $_iHlp;

    public function __construct(
        Availability $stockAvailability,
        ProtectedCode $helperProtectedCode,
        Item $helperItem,
        \Unirgy\Dropship\Observer\Context $context,
        array $data = []
    )
    {
        $this->_stockAvailability = $stockAvailability;
        $this->_hlpPr = $helperProtectedCode;
        $this->_iHlp = $helperItem;

        parent::__construct($context, $data);
    }

    /**
     * Check quote items stock level qty live
     *
     * @param DataObject $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->_hlp->isActive()) {
            $this->unsQuoteItem();
            return;
        }
        //return $this; //disabled
        $quoteItem = $observer->getEvent()->getItem();
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        if (!$quoteItem || !$quoteItem->getProductId() || $quoteItem->getQuote()->getIsSuperMode()) {
            $this->unsQuoteItem();
            return $this;
        }
        /* //deprecated
        if ($quoteItem->getHasError()) {
            $availability = $this->_stockAvailability;
            $store = $quoteItem->getStoreId();
            $vendor = $this->_helperData->getVendor($quoteItem->getProduct());
            if ($availability->getUseLocalStockIfAvailable($store, $vendor)) {
                $quoteItem->setHasError(false);
            }
        }
        */
        try {
            $hlp = $this->_hlpPr;
            $items = array($quoteItem);
            $this->_eventManager->dispatch('udropship_prepare_quote_items_before', array('items'=>$items));
            $hlp->applyDefaultVendorIds($items)->applyStockAvailability($items);
            $this->_iHlp->initBaseCosts($items);
            $this->_eventManager->dispatch('udropship_prepare_quote_items_after', array('items'=>$items));
        } catch (\Exception $e) {
            // all necessary actions should be already done by now, kill the exception
        }
        $this->unsQuoteItem();
        return $this;
    }
}
