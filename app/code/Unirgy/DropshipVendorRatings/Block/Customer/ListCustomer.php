<?php

namespace Unirgy\DropshipVendorRatings\Block\Customer;

use Magento\Framework\View\Element\Template\Context;
use Unirgy\DropshipVendorRatings\Block\Customer\ListCustomer\AbstractList;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class ListCustomer extends AbstractList
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    public function __construct(Context $context, 
        HelperData $helperData, 
        array $data = [])
    {
        $this->_rateHlp = $helperData;

        parent::__construct($context, $data);
    }

    protected $_reviewCollection;
    public function getReviewsCollection()
    {
        if (null === $this->_reviewCollection) {
            $this->_reviewCollection = $this->_rateHlp->getCustomerReviewsCollection();
        }
        return $this->_reviewCollection;
    }
}