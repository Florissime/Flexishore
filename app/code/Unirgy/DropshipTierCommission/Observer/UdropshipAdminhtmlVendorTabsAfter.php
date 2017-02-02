<?php

namespace Unirgy\DropshipTierCommission\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use Unirgy\DropshipTierCommission\Helper\Data as HelperData;

class UdropshipAdminhtmlVendorTabsAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Layout
     */
    protected $_viewLayout;

    public function __construct(HelperData $helper, Layout $viewLayout)
    {
        // i think this should be possible to remove altoghether and use $block->getLayout() in execute()
        $this->_viewLayout = $viewLayout;
        parent::__construct($helper);

    }

    public function execute(Observer $observer)
    {
        $block = $observer->getBlock();
        $block->addTab('udtiercom', [
            'label'     => __('Tier Commissions'),
            'after'     => 'shipping_section',
            'content'   => $this->_viewLayout->createBlock('Unirgy\DropshipTierCommission\Block\Adminhtml\VendorEditTab\ComRates\Form', 'vendor.tiercom.form')
                ->toHtml()
        ]);
    }
}
