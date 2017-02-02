<?php


namespace Unirgy\DropshipVendorProduct\Model\SystemConfig\Backend;

use Magento\Framework\App\Config\Value;

class EmptyBackend extends Value
{
    public function beforeSave()
    {
        $this->setValue('');
        return parent::beforeSave();
    }
}
