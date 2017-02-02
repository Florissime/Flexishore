<?php

namespace Unirgy\DropshipVendorRatings\Controller\Vendor;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\Dropship\Helper\Data as HelperData;

class Index extends AbstractVendor
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        Context $context,
        HelperData $helperData
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context);
    }

    public function execute()
    {
        $_vendor = $this->_hlp->getVendor($this->getRequest()->getParam('id'));
        if (!$_vendor->getId()) {
            $this->_redirect('/');
            return $this;
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
