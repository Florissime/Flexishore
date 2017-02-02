<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Urlkey extends AbstractElement
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
        $this->_renderer = $this->_modelLayout->createBlock('Unirgy\DropshipMicrositePro\Block\Vendor\Register\Renderer\Urlkey');
        return parent::getHtml();
    }
}