<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form;

use \Magento\Framework\Data\Form\Element\AbstractElement;

class HandlingConfig extends AbstractElement
{
    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendHelper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
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
        $this->_renderer = $this->_layout->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Renderer\HandlingConfig');
        return parent::getHtml();
    }
}