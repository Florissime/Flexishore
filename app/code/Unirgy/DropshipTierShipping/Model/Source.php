<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
namespace Unirgy\DropshipTierShipping\Model;

use Unirgy\DropshipTierShipping\Helper\Data as DropshipTierShippingHelperData;
use Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType\Collection;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipTierShippingHelperData
     */
    protected $_tsHlp;

    /**
     * @var Collection
     */
    protected $_deliveryTypeCollection;

    public function __construct(
        HelperData $helperData, 
        DropshipTierShippingHelperData $dropshipTierShippingHelperData, 
        Collection $deliverytypeCollection,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_tsHlp = $dropshipTierShippingHelperData;
        $this->_deliveryTypeCollection = $deliverytypeCollection;

        parent::__construct($data);
    }

    const CM_MAX_FIRST_ADDITIONAL = 1;
    const CM_SUM_FIRST_ADDITIONAL = 2;
    const CM_MULTIPLY_FIRST       = 3;
    const CM_MAX_FIRST = 4;
    const CM_SUM_FIRST = 5;

    const CT_SEPARATE = 1;
    const CT_BASE_PLUS_ZONE_PERCENT = 2;
    const CT_BASE_PLUS_ZONE_FIXED   = 3;

    const FL_VENDOR_BASE = 1;
    const FL_VENDOR_DEFAULT = 2;
    const FL_TIER = 2;

    const USE_RATES_V1 = 0;
    const USE_RATES_V1_SIMPLE = 1;
    const USE_RATES_V2 = 2;
    const USE_RATES_V2_SIMPLE = 3;
    const USE_RATES_V2_SIMPLE_COND = 4;

    const SIMPLE_COND_FULLWEIGHT = 'full_weight';
    const SIMPLE_COND_SUBTOTAL = 'subtotal';
    const SIMPLE_COND_TOTALQTY = 'total_qty';

    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $hlpv = $this->_tsHlp;

        switch ($this->getPath()) {

        case 'carriers/udtiership/additional_calculation_type':
        case 'carriers/udtiership/cost_calculation_type':
        case 'carriers/udtiership/handling_calculation_type':
            $options = [
                self::CT_SEPARATE => __('Separate per customer shipclass'),
                self::CT_BASE_PLUS_ZONE_PERCENT => __('Base plus percent per customer shipclass'),
                self::CT_BASE_PLUS_ZONE_FIXED   => __('Base plus fixed per customer shipclass'),
            ];
            break;
        case 'carriers/udtiership/calculation_method':
            $options = [
                self::CM_MAX_FIRST_ADDITIONAL => __('Max first item other additional'),
                self::CM_MAX_FIRST => __('Max first item (discard qty)'),
                self::CM_SUM_FIRST_ADDITIONAL => __('Sum first item other additional'),
                self::CM_SUM_FIRST => __('Sum first item (discard qty)'),
                self::CM_MULTIPLY_FIRST       => __('Multiply first item (additional not used)'),
            ];
            break;

        case 'carriers/udtiership/fallback_lookup':
            $options = [
                self::FL_VENDOR_BASE => __('Vendor up to BASE'),
                self::FL_VENDOR_DEFAULT => __('Vendor up to DEFAULT'),
                self::FL_TIER => __('Vendor/Global by tier'),
            ];
            break;

        case 'carriers/udtiership/handling_apply_method':
            $options = [
                'none'      => 'None',
                'fixed'     => 'Fixed Per Category',
                'fixed_max' => 'Max Fixed',
                'percent'   => 'Percent',
            ];
            break;

        case 'carriers/udtiership/use_simple_rates':
           $options = [
               //self::USE_RATES_V1 => __('V1 Rates'),
               //self::USE_RATES_V1_SIMPLE => __('V1 Simple Rates'),
               self::USE_RATES_V2 => __('V2 By Category/VendorClass First/Additional/Handling Rates'),
               self::USE_RATES_V2_SIMPLE => __('V2 Simple First/Additional Rates'),
               self::USE_RATES_V2_SIMPLE_COND => __('V2 Simple Conditional Rates'),
           ];
           break;

        case 'simple_condition':
            $options = [
                self::SIMPLE_COND_FULLWEIGHT => __('Full Weight'),
                self::SIMPLE_COND_SUBTOTAL => __('Subtotal'),
                self::SIMPLE_COND_TOTALQTY => __('Total Qty'),
            ];
            break;

        case 'tiership_delivery_type_selector':
        case 'carriers/udtiership/delivery_type_selector':
            $selector = true;
            $options = $this->_deliveryTypeCollection->toOptionHash();
            break;

        case 'carriers/udtiership/free_method':
            $selector = false;
            $options = $this->_deliveryTypeCollection->toOptionHash();
            break;

        default:
            throw new \Exception(__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = [''=>__('* Please select')] + $options;
        }

        return $options;
    }
}