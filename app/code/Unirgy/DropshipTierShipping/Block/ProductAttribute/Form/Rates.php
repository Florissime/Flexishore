<?php

namespace Unirgy\DropshipTierShipping\Block\ProductAttribute\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\ProductAttribute\Form
 */
class Rates extends AbstractElement
{
    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * Rates constructor.
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param LayoutInterface $layout
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        LayoutInterface $layout,
        $data = []
    ) {
        $this->_layout = $layout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        $this->_renderer = $this->_layout->createBlock('Unirgy\DropshipTierShipping\Block\ProductAttribute\Renderer\Rates');
        return parent::getHtml();
    }
}
