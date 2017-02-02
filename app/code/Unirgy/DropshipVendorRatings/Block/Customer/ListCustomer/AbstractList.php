<?php

namespace Unirgy\DropshipVendorRatings\Block\Customer\ListCustomer;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Block\Items\AbstractItems;

abstract class AbstractList extends AbstractItems
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($toolbar = $this->getLayout()->getBlock('udratings_list.toolbar')) {
            $toolbar->setCollection($this->getReviewsCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }
    abstract public function getReviewsCollection();
}