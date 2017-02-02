<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Country extends AbstractElement
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    public function __construct(Factory $factoryElement, 
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        \Magento\Framework\View\LayoutInterface $layout,
        $data = []
    )
    {
        $this->_layout = $layout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

	public function getHtml()
    {
        $this->_renderer = $this->_layout->createBlock('\Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer\Country');
        return parent::getHtml();
    }
}