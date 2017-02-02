<?php

namespace Unirgy\DropshipTierShipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

class UdropshipAdminhtmlVendorTabsAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(HelperData $helperData, 
        StoreManagerInterface $modelStoreManagerInterface, 
        Layout $viewLayout)
    {
        $this->_viewLayout = $viewLayout;

        parent::__construct($helperData, $modelStoreManagerInterface);
    }

    public function execute(Observer $observer)
    {
        $tsHlp = $this->_tsHlp;
        $block = $observer->getBlock();
        if (!$tsHlp->isV2Rates()) {
            $block->addTab('udtiership', [
                'label'     => __('Shipping Rates'),
                'after'     => 'shipping_section',
                'content'   => $this->_viewLayout->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Form', 'vendor.tiership.form')
                    ->toHtml()
            ]);
        } else {
            $block->addTab('udtiership', [
                'label'     => __('Shipping Rates'),
                'after'     => 'shipping_section',
                'content'   => $this->_viewLayout->createBlock('\Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form')
                    ->toHtml()
            ]);
        }
    }
}
