<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor\Register\Form;

class Termcond extends \Magento\Framework\Data\Form\Element\Checkboxes
{
    public function getLabelHtml($idSuffix = '', $scopeLabel = '')
    {
        return '';
    }
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
            . ' <label for="'.$id.'">' . $this->getLabel() . ( $this->getRequired() ? ' <span class="required">*</span>' : '' ) . '</label></li>'
            . "\n";
        return $html;
    }
}