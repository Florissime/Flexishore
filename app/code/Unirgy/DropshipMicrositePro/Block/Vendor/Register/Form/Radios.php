<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Radios as ElementRadios;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\ArrayUtils;

class Radios extends ElementRadios
{
    /**
     * @var ArrayUtils
     */
    protected $_stdlibArrayUtils;

    public function __construct(Factory $factoryElement, 
        CollectionFactory $factoryCollection, 
        Escaper $escaper, 
        ArrayUtils $stdlibArrayUtils)
    {
        $this->_stdlibArrayUtils = $stdlibArrayUtils;

        parent::__construct($factoryElement, $factoryCollection, $escaper);
    }

    public function getElementHtml()
    {
        $html = '';
        $value = $this->getValue();
        if ($values = $this->getValues()) {
            $values = $this->_stdlibArrayUtils->decorateArray($values);
            foreach ($values as $option) {
                $html.= $this->_optionToHtml($option, $value);
            }
        }
        $html.= $this->getAfterElementHtml();
        return $html;
    }
    protected function _optionToHtml($option, $selected)
    {
        $isLast = false;
        if (is_array($option)) {
            $isLast = @$option['decorated_is_last'];
        } elseif ($option instanceof DataObject) {
            $isLast = @$option->getDecoratedIsLast();
        }
        if ($this->getRequired() && $isLast) {
            $this->addClass('udvalidate-radios');
        } else {
            $this->removeClass('required-entry');
        }
        $html = '<input type="radio"'.$this->serialize(['name', 'class', 'style']);
        if (is_array($option)) {
            $html.= 'value="'.$this->_escape($option['value']).'"  id="'.$this->getHtmlId().$option['value'].'"';
            if ($option['value'] == $selected) {
                $html.= ' checked="checked"';
            }
            $html.= ' />';
            $html.= '<label class="inline" for="'.$this->getHtmlId().$option['value'].'">'.$option['label'].'</label>';
        }
        elseif ($option instanceof DataObject) {
            $html.= 'id="'.$this->getHtmlId().$option->getValue().'"'.$option->serialize(['label', 'title', 'value', 'class', 'style']);
            if (in_array($option->getValue(), $selected)) {
                $html.= ' checked="checked"';
            }
            $html.= ' />';
            $html.= '<label class="inline" for="'.$this->getHtmlId().$option->getValue().'">'.$option->getLabel().'</label>';
        }
        $html.= $this->getSeparator() . "\n";
        return $html;
    }
}