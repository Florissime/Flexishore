<?php

namespace Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField;



class CategoriesMultiSelect extends CategoriesSelect
{
    protected function _getTypeBlockClass()
    {
        return '\Unirgy\Dropship\Block\CategoriesMultiSelect';
    }

}