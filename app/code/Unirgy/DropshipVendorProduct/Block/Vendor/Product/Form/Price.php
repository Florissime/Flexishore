<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Price as FormPrice;

class Price extends FormPrice
{
    public function getEscapedValue($index=null)
    {
        $value = $this->getValue();

        if (substr($value, 0, 1)=='$') {
            return $value;
        } elseif (!is_numeric($value)) {
            return null;
        }

        return number_format($value, 2, null, '');
    }
    public function getLabelHtml($idSuffix = '', $scopeLabel = '')
    {
        if ($this->getLabel() !== null) {
            $extraHml = '';

            if ($attribute = $this->getEntityAttribute()) {
                $store = $this->getStore($attribute);
                if ($this->getType() !== 'hidden') {
                    $extraHml .= ' <strong>'
                        . $this->_localeCurrency->getCurrency($store->getBaseCurrencyCode())->getSymbol()
                        . '</strong>';
                }
                if ($this->_taxData->priceIncludesTax($store)) {
                    if ($attribute->getAttributeCode() !== 'cost') {
                        $addJsObserver = true;
                        $extraHml .= ' <strong>[' . __(
                                'Inc. Tax'
                            ) . '<span id="dynamic-tax-' . $attribute->getAttributeCode() . '"></span>]</strong>';
                    }
                }
            }

            $html = '<label class="label admin__field-label" for="' .
                $this->getHtmlId() . $idSuffix . '"' . $this->_getUiId(
                    'label'
                ) . '><span>' . $this->_escape(
                    $this->getLabel()
                ) . $extraHml . '</span></label>' . "\n";
        } else {
            $html = '';
        }
        return $html;
    }
    public function getAfterElementHtml()
    {
        return \Magento\Framework\Data\Form\Element\AbstractElement::getAfterElementHtml();
    }
}