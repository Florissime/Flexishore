<?php

namespace Unirgy\DropshipVendorProduct\Model;

use Magento\Framework\App\ObjectManager;

class ProductStatus extends \Magento\Catalog\Model\Product\Attribute\Source\Status
{
    const STATUS_PENDING    = 3;
    const STATUS_FIX        = 4;
    const STATUS_DISCARD    = 5;
    const STATUS_VACATION   = 6;
    const STATUS_SUSPENDED   = 7;
    static public function getOptionArray()
    {
        $res = [
            self::STATUS_ENABLED    => __('Enabled'),
            self::STATUS_DISABLED   => __('Disabled'),
            self::STATUS_PENDING    => __('Pending'),
            self::STATUS_FIX        => __('Fix'),
            self::STATUS_DISCARD    => __('Discard'),
            self::STATUS_VACATION   => __('Vacation')
        ];
        /** @var \Unirgy\Dropship\Helper\Data $hlp */
        $hlp = ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data');
        if ($hlp->isModuleActive('Unirgy_DropshipVendorMembership')) {
            $res[self::STATUS_SUSPENDED] = __('Suspended');
        }
        return $res;
    }
    public function getAllOptions()
    {
        $res = [
            [
                'value' => '',
                'label' => __('-- Please Select --')
            ]
        ];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = [
               'value' => $index,
               'label' => $value
            ];
        }
        return $res;
    }
}