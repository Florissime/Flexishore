<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class QuickCreate extends AbstractElement
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_modelLayout;

    public function __construct(Factory $factoryElement, 
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        \Magento\Framework\View\LayoutInterface $modelLayout,
        $data = []
    )
    {
        $this->_modelLayout = $modelLayout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

	public function getHtml()
    {
        $this->_renderer = $this->_modelLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\QuickCreate');
        $this->_renderer->setProduct($this->_product);
        $this->_renderer->setCfgAttribute($this->getCfgAttribute());
        $this->_renderer->setCfgAttributeValue($this->getCfgAttributeValue());
        $this->_renderer->setCfgAttributeValueTuple($this->getCfgAttributeValueTuple());
        $this->_renderer->setCfgAttributeLabel($this->getCfgAttributeLabel());
        $html = parent::getHtml();
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