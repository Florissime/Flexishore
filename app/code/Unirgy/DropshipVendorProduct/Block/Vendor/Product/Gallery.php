<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product;

use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;

class Gallery extends GalleryBase
{
    public function getContentHtml()
    {
        if (0&&$this->registry->registry('current_product')->getTypeId()=='configurable') {
            $content = $this->_layout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\GalleryCfgContentExs');
        } else {
            $content = $this->_layout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\GalleryContent');
        }
        $content->setId($this->getHtmlId() . '_content')->setElement($this);
        $galleryJs = $content->getJsObjectName();
        $content->getUploader()->getConfig()->setMegiaGallery($galleryJs);
        return $content->toHtml();
    }
    public function setValue($value)
    {
        parent::setValue($value);
        return $this;
    }
}