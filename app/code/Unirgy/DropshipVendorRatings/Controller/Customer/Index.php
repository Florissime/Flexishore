<?php

namespace Unirgy\DropshipVendorRatings\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Index extends AbstractCustomer
{
    public function execute()
    {
        $this->_view->loadLayout();
        if ($navigationBlock = $this->_view->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('sales/udropship/review/review_pending');
        }
        $this->_view->renderLayout();
    }
}
