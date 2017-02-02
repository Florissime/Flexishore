<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product;

class CfgUploader extends \Magento\Backend\Block\Media\Uploader
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/cfguploader.phtml');
    }
}