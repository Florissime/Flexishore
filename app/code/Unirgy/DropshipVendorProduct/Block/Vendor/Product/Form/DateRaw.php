<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Form;

class DateRaw extends \Magento\Framework\Data\Form\Element\Date
{
    public function setValue($value)
    {
        return parent::setData('value', $value);
    }
    public function getValue($format = null)
    {
        return parent::getData('value');
    }
}