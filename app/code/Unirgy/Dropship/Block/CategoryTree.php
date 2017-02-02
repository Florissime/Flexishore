<?php

namespace Unirgy\Dropship\Block;

use \Magento\Catalog\Model\Category;

class CategoryTree extends \Magento\Catalog\Block\Adminhtml\Category\Tree
{
    protected function _prepareLayout()
    {
        return \Magento\Framework\View\Element\AbstractBlock::_prepareLayout();
    }
}