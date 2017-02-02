<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Framework\Data\Form\Element\Text;

class StockDataQty extends Text
{
    public function getAfterElementHtml()
    {
        $name = $this->getData('name');
        $name = str_replace('qty', 'original_inventory_qty', $name);
        if ($suffix = $this->getForm()->getFieldNameSuffix()) {
            $name = $this->getForm()->addSuffixToName($name, $suffix);
        }
        $html = sprintf('<input name="%s" type="hidden" value="%s" />', $name, $this->getEscapedValue());

        $html .= parent::getAfterElementHtml();
        return $html;
    }
}