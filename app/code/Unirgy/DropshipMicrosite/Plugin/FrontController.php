<?php

namespace Unirgy\DropshipMicrosite\Plugin;

class FrontController
{
    /** @var \Unirgy\Dropship\Helper\Data */
    protected $_hlp;
    /** @var \Unirgy\DropshipMicrosite\Helper\Data */
    protected $_msHlp;
    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $micrositeHelper;
    }
    public function beforeDispatch(\Magento\Framework\App\FrontController $subject, \Magento\Framework\App\RequestInterface $request)
    {
        if (($vendor = $this->_msHlp->getCurrentVendor())) {
            $this->_hlp->getObj('\Magento\Framework\Registry')->register('useVendorUrl', 1);
            $this->_hlp->setScopeConfig('web/default/front', 'umicrosite');
        }
    }
}