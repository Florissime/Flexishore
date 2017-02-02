<?php

namespace Unirgy\DropshipTierShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Unirgy\DropshipTierShipping\Model\Source;

/**
 * Class Rate
 * @package Unirgy\DropshipTierShipping\Model\ResourceModel
 */
class Rate extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
    }

    /**
     * @param $type
     * @param bool $isVendor
     * @param bool $isProduct
     */
    public function useRateSetup($type, $isVendor = false, $isProduct = false)
    {
        if ($isProduct) {
            $this->_init('udtiership_product_rates', 'rate_id');
        } else {
            switch ($type) {
                case Source::USE_RATES_V2:
                    if ($isVendor) {
                        $this->_init('udtiership_vendor_rates', 'rate_id');
                    } else {
                        $this->_init('udtiership_rates', 'rate_id');
                    }
                    break;
                case Source::USE_RATES_V2_SIMPLE:
                    if ($isVendor) {
                        $this->_init('udtiership_vendor_simple_rates', 'rate_id');
                    } else {
                        $this->_init('udtiership_simple_rates', 'rate_id');
                    }
                    break;
                case Source::USE_RATES_V2_SIMPLE_COND:
                    if ($isVendor) {
                        $this->_init('udtiership_vendor_simple_cond_rates', 'rate_id');
                    } else {
                        $this->_init('udtiership_simple_cond_rates', 'rate_id');
                    }
                    break;
            }
        }
    }

}
