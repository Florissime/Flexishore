<?php

namespace Unirgy\DropshipMicrositePro\Block\Vendor;

class ProductList extends \Magento\Catalog\Block\Product\ListProduct
{
    protected function getPriceRender()
    {
        return $this->_layout->createBlock(
            'Magento\Framework\Pricing\Render',
            '',
            ['data' => ['price_render_handle' => 'catalog_product_prices']]
        );
    }
}