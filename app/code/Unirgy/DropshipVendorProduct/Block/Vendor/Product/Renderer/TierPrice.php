<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Price\Tier;

class TierPrice extends Tier
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Unirgy_DropshipVendorProduct::unirgy/udprod/vendor/product/renderer/tier_price.phtml');
    }
    public function getAddButtonHtml()
    {
        $this->getChildBlock('add_button')->setTemplate('Unirgy_DropshipVendorProduct::widget/button.phtml');
        return $this->getChildHtml('add_button');
    }
}
