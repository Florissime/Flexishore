<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\View\Element\Template;

class DownloadableFieldset extends Template implements RendererInterface
{
    protected $_element;

    protected function _construct()
    {
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/downloadable_fieldset.phtml');
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function render(AbstractElement $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }
    public function getChildElementHtml($elem='_downloadable')
    {
        return $this->getElement()->getForm()->getElement($elem)->toHtml();
    }
    public function getChildElement($elem='_downloadable')
    {
        return $this->getElement()->getForm()->getElement($elem);
    }
    protected $_product;
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }
    public function getProduct()
    {
        return $this->_product;
    }
}