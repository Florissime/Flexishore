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
namespace Unirgy\DropshipTierCommission\Model;

use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;
use Unirgy\DropshipTierCommission\Helper\Data as DropshipTierCommissionHelperData;

/**
 * Class Source
 *
 * @method string getPath()
 * @method Source setPath(string $path)
 * @package Unirgy\DropshipTierCommission\Model
 */
class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * @var DropshipTierCommissionHelperData
     */
    protected $_dropshipTierCommissionHelper;

    /**
     * Source constructor.
     * @param HelperData $helper
     * @param DropshipTierCommissionHelperData $dropshipTierCommissionHelper
     * @param array $data
     */
    public function __construct(
        HelperData $helper,
        DropshipTierCommissionHelperData $dropshipTierCommissionHelper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_dropshipTierCommissionHelper = $dropshipTierCommissionHelper;

        parent::__construct($data);
    }

    /**
     * @param bool $selector
     * @return array
     * @throws \Exception
     */
    public function toOptionHash($selector = false)
    {
        $hlp = $this->_helper;
        $hlpv = $this->_dropshipTierCommissionHelper;

        switch ($this->getPath()) {

            case 'udropship/tiercom/fixed_rule':
            case 'tiercom_fixed_rates':
                $options = [
                    'item_price' => __('Item Price')
                ];
                if ($this->getPath() == 'tiercom_fixed_rates') {
                    $options = ['' => __('* Use Global Config')] + $options;
                }
                break;

            case 'udropship/tiercom/fallback_lookup':
            case 'tiercom_fallback_lookup':
                $options = [
                    'vendor' => __('Vendor First'),
                    'tier' => __('Tier First')
                ];
                if ($this->getPath() == 'tiercom_fallback_lookup') {
                    $options = ['-1' => __('* Use Global Config')] + $options;
                }
                break;

            case 'udropship/tiercom/fixed_calculation_type':
            case 'tiercom_fixed_calc_type':
                $options = [
                    'flat' => __('Flat (per po)'),
                    'tier' => __('Tier (per item)'),
                    'rule' => __('Rule Based (per item)'),
                    'flat_rule' => __('Tier + Rule Based'),
                    'flat_tier' => __('Flat + Tier'),
                    'flat_rule' => __('Flat + Rule Based'),
                    'flat_tier_rule' => __('Flat + Tier + Rule Based'),
                ];
                if ($this->getPath() == 'tiercom_fixed_calc_type') {
                    $options = ['' => __('* Use Global Config')] + $options;
                }
                break;

            default:
                throw new \Exception(__('Invalid request for source options: ' . $this->getPath()));
        }

        if ($selector) {
            $options = ['' => __('* Please select')] + $options;
        }

        return $options;
    }
}
