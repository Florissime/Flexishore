<?php

namespace Unirgy\Dropship\Controller\Vendor;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Controller\Result\RedirectFactory;
use \Magento\Framework\Model\Date;
use \Magento\Framework\View\DesignInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Logout extends AbstractVendor
{
    public function execute()
    {
        $this->_getSession()->logout();
        return $this->resultRedirectFactory->create()->setPath('udropship/vendor');
    }
}
