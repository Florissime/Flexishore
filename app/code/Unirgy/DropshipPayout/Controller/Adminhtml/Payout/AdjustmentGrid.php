<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;

class AdjustmentGrid extends AbstractPayout
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents(
            $this->_view->getLayout()
                ->createBlock('\Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab\Adjustments', 'admin.udpayout.adjustments')
                ->setPayoutId($this->getRequest()->getParam('id'))
                ->toHtml()
        );
    }
}
