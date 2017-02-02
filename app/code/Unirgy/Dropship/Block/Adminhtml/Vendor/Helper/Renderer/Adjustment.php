<?php

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Renderer;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use \Magento\Framework\View\LayoutFactory;
use \Unirgy\Dropship\Model\Source;

class Adjustment extends Widget implements RendererInterface
{
    protected $_element = null;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $helper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = [])
    {
        $this->_hlp = $helper;
        return parent::__construct($context, $data);
    }

    public function _construct()
    {
        $this->setTemplate('Unirgy_Dropship::udropship/vendor/statement/adjustment.phtml');
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

    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(array(
                    'label' => __('Delete'),
                    'class' => 'delete delete-option'
                )));
        $this->setChild('add_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(array(
                    'label' => __('Add Adjustment'),
                    'class' => 'add',
                    'id'    => 'add_new_option_button'
                )));
        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
    
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }
    
    public function getPoTypeSelect($name, $id=null)
    {
        $select = $this->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select')
            ->setClass('required-entry validate-state')
            ->setValue($this->getStatement()->getPoType())
            ->setOptions($this->_hlp->src()->setPath('statement_po_type')->toOptionHash());

        $select->setName($name);
        if (!is_null($id)) $select->setId($id);
            
        return $select->getHtml();
    }

}