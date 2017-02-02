<?php


namespace Unirgy\Dropship\Model\SystemConfig\Backend;

use \Magento\Framework\App\Config\Value;

class EmptyValue extends Value
{
    public function beforeSave()
    {
        $this->setValue('');
        return parent::beforeSave();
    }
}
