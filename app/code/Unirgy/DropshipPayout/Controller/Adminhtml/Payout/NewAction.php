<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Framework\Controller\ResultFactory;

class NewAction extends AbstractPayout
{
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
