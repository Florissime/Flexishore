<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Carriers;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use \Magento\Framework\Registry;
use \Magento\Framework\View\LayoutFactory;

class Grid
    extends Widget
    implements RendererInterface
{
    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    protected $_element = null;
    protected $_customerGroups = null;
    protected $_websites = null;

    public function __construct(Context $context, 
        array $data = [], 
        Registry $frameworkRegistry = null
    )
    {
        $this->_frameworkRegistry = $frameworkRegistry;

        $this->setTemplate('Unirgy_Dropship::udropship/carriers.phtml');
    }

    public function getProduct()
    {
        return $this->_frameworkRegistry->registry('product');
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

    public function getValues()
    {
        $values =array();
        $data = $this->getElement()->getValue();

        if (is_array($data)) {
            usort($data, array($this, '_sortCarriers'));
            $values = $data;
        }
        return $values;
    }

    protected function _sortCarriers($a, $b)
    {
        return 0;
    }

    protected function _prepareLayout()
    {
        $this->setChild('add_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(array(
                    'label'     => __('Add Carrier'),
                    'onclick'   => 'carrierControl.addItem()',
                    'class' => 'add'
                )));
        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
}