<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Model\UrlFactory;
use Magento\Framework\View\LayoutFactory;

class GalleryContent extends Content
{
    /**
     * @var \Magento\Framework\Url
     */
    protected $_urlBuilder;

    protected $_template = 'Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/gallery.phtml';

    public function __construct(
        \Magento\Framework\Url $urlBuilder,
        Context $context,
        EncoderInterface $jsonEncoder, 
        Config $mediaConfig, 
        array $data = []
    )
    {
        $this->_urlBuilder = $urlBuilder;

        parent::__construct($context, $jsonEncoder, $mediaConfig, $data);
    }
    protected function _prepareLayout()
    {
        $this->addChild('uploader', '\Unirgy\DropshipVendorProduct\Block\Vendor\Product\Uploader');

        $this->getUploader()->getConfig()->setUrl(
            $this->_urlBuilder->addSessionParam()->getUrl('udprod/vendor/upload')
        )->setFileField(
            'image'
        )->setFilters(
            [
                'images' => [
                    'label' => __('Images (.gif, .jpg, .png)'),
                    'files' => ['*.gif', '*.jpg', '*.jpeg', '*.png'],
                ],
            ]
        );

        $this->_eventManager->dispatch('udprod_gallery_prepare_layout', ['block' => $this]);

        return \Magento\Backend\Block\Widget::_prepareLayout();
    }

    public function getUploaderHtml()
    {
        return $this->getUploader()->toHtml();
    }

    public function hasUseDefault()
    {
        return false;
    }
}