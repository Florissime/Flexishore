<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField\FieldContainer;

class CfgAttributesSelector extends FieldContainer
{
    public function _construct()
    {
        parent::_construct();
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipVendorProduct::udprod/system/cfg_attributes_selector.phtml');
        }
    }
    public function prepareIdSuffix($id)
    {
        return md5($id);
    }
    
}
