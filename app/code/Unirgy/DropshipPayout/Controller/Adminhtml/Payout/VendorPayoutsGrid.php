<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;

class VendorPayoutsGrid extends AbstractPayout
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents(
            $this->_view->getLayout()
                ->createBlock('\Unirgy\DropshipPayout\Block\Adminhtml\Vendor\Payout\Grid', 'admin.udpayout.rows')
                ->setVendorId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }
}
