<?php

namespace Unirgy\DropshipPayout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipAdminhtmlVendorTabsAfter extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        $id = $observer->getEvent()->getId();
        $v = $this->_hlp->getVendor($id);

        if ($this->_payoutHlp->isVendorEnabled($v)) {
            $block->addTab('payouts_section', [
                'label'     => __('Payouts'),
                'title'     => __('Payouts'),
                'content'   => $block->getLayout()->createBlock('\Unirgy\DropshipPayout\Block\Adminhtml\Vendor\Payout\Grid', 'udropship.payout.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ]);
        }
    }
}
