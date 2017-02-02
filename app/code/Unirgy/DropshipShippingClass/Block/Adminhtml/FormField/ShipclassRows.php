<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\FormField;

class ShipclassRows extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    protected $_layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        $data = []
    )
    {
        $this->_layout = $layout;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }
    public function getHtml()
    {
        $this->_renderer = $this->_layout->createBlock('\Unirgy\DropshipShippingClass\Block\Adminhtml\FormRenderer\ShipclassRows');
        return parent::getHtml();
    }
}