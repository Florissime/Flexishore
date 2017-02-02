<?php

namespace Unirgy\DropshipVendorRatings\Block\Customer\ListCustomer;

use Magento\Framework\View\Element\Template\Context;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Pending extends AbstractList
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
            $this->_reviewCollection = $this->_rateHlp->getPendingCustomerReviewsCollection();
        }
        return $this->_reviewCollection;
    }
    
}