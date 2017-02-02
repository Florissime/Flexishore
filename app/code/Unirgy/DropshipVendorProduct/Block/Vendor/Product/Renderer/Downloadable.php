<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable as TabDownloadable;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;

class Downloadable extends TabDownloadable implements RendererInterface
{
    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    public function __construct(Context $context, 
        Registry $registry, 
        LayoutFactory $viewLayoutFactory, 
        array $data = [])
    {
        $this->_viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context, $registry, $data);
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/downloadable.phtml');
    }
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }
    protected function _toHtml()
    {
        $accordion = $this->_viewLayoutFactory->create()->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Widget\Accordion')
            ->setId('downloadableInfo');

        $accordion->addItem('samples', [
            'title'   => __('Samples'),
            'content' => $this->_viewLayoutFactory->create()
                ->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Downloadable\Samples')->toHtml(),
            'open'    => false,
        ]);

        $accordion->addItem('links', [
            'title'   => __('Links'),
            'content' => $this->_viewLayoutFactory->create()->createBlock(
                'udprod/vendor_product_renderer_downloadable_links',
                'catalog.product.edit.tab.downloadable.links')->toHtml(),
            'open'    => true,
        ]);

        $this->setChild('accordion', $accordion);

        return Template::_toHtml();
    }
}