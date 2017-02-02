<?php

namespace Unirgy\DropshipTierShipping\Block\Vendor\Product\Renderer;

use Unirgy\DropshipTierShipping\Block\ProductAttribute\Renderer\Rates as RendererRates;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Block\Vendor\Product\Renderer
 */
class Rates extends RendererRates
{
    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Unirgy_DropshipTierShipping::unirgy/tiership/vendor/v2/product/rates.phtml');
    }
}
