<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class Index extends AbstractRegistration
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        return $resultPage;
    }
}
