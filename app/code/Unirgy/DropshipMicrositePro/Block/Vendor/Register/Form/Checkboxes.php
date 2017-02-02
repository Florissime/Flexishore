<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

use Magento\Framework\Data\Form\Element\Checkboxes as ElementCheckboxes;

class Checkboxes extends ElementCheckboxes
{
    protected function _optionToHtml($option)
    {
        $id = $this->getHtmlId().'_'.$this->_escape($option['value']);

        $html = '<li><input id="'.$id.'"';
        foreach ($this->getHtmlAttributes() as $attribute) {
            if ($value = $this->getDataUsingMethod($attribute)) {
                $html .= ' '.$attribute.'="'.$value.'"';
            }
        }
        $html .= ' value="'.$option['value'].'" />'
            . ' <label for="'.$id.'">' . $option['label'] . '</label></li>'
            . "\n";
        return $html;
    }
}