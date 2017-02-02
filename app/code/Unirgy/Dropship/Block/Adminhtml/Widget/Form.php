<?php
 
namespace Unirgy\Dropship\Block\Adminhtml\Widget;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected function _getAdditionalElementTypes()
    {
        return array_merge(parent::_getAdditionalElementTypes(), array(
            'udropship_vendor'=>'\Unirgy\Dropship\Block\Vendor\Htmlselect'
        ));
    }
}