<?php

namespace Unirgy\DropshipTierCommission\Block\Adminhtml;

use Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\Rows;

class VendorStatementEditTabRows extends Rows
{
    protected function _prepareColumns()
    {
        $this->addColumn('sku', [
            'header'    => __('SKU'),
            'index'     => 'sku'
        ]);
        $this->addColumn('vendor_sku', [
            'header'    => __('Vendor SKU'),
            'index'     => 'vendor_sku'
        ]);
        $this->addColumn('product', [
            'header'    => __('Product'),
            'index'     => 'product'
        ]);
        $this->addColumnsOrder('sku', 'po_increment_id');
        $this->addColumnsOrder('sku', 'vendor_sku');
        $this->addColumnsOrder('product', 'sku');
        return parent::_prepareColumns();
    }
}