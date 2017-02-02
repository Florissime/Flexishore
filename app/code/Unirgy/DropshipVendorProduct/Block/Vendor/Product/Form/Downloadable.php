<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Downloadable extends AbstractElement
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_modelLayout;

    public function __construct(Factory $factoryElement, 
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        \Magento\Framework\View\LayoutInterface $modelLayout)
    {
        $this->_modelLayout = $modelLayout;

        parent::__construct($factoryElement, $factoryCollection, $escaper);
    }

    public function getHtml()
    {
        $this->_renderer = $this->_modelLayout->createBlock('udprod/vendor_product_renderer_downloadable', 'admin.product.options');
        $this->_renderer->setProduct($this->_product);
        return parent::getHtml();
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