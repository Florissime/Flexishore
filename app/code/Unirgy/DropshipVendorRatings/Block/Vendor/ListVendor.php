<?php

namespace Unirgy\DropshipVendorRatings\Block\Vendor;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class ListVendor extends Template
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    /**
     * @var DropshipMicrositeHelperData
     */
    protected $_msHlp;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        Context $context,
        HelperData $helperData,
        DropshipMicrositeHelperData $dropshipMicrositeHelperData, 
        DropshipHelperData $dropshipHelperData, 
        array $data = [])
    {
        $this->_rateHlp = $helperData;
        $this->_msHlp = $dropshipMicrositeHelperData;
        $this->_hlp = $dropshipHelperData;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        if ($toolbar = $this->getLayout()->getBlock('udratings_list.toolbar')) {
            $toolbar->setCollection($this->getReviewsCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }
    protected $_reviewCollection;
    public function getReviewsCollection()
    {
        if (null === $this->_reviewCollection) {
            $this->_reviewCollection = $this->_rateHlp->getVendorReviewsCollection($this->getVendor()->getId());
        }
        return $this->_reviewCollection;
    }
    public function getVendor()
    {
        $vId = $this->getRequest()->getParam('id');
        $vId = $vId ? $vId : $this->_msHlp->getCurrentVendor()->getId();
        return $this->_hlp->getVendor($vId);
    }
    public function getSize()
    {
        return $this->getReviewsCollection()->getSize();
    }
    public function getAddressFormatted($review)
    {
        $addrStr = '';
        if (($__sa = $review->getShippingAddress())) {
            $addrStr = $__sa->getCity();
            if ($__sa->getRegionCode()) {
                $addrStr .= ', '.$__sa->getRegionCode();
            }
            $addrStr .= ', '.$__sa->getCountry();
        }
        return $addrStr;
    }
}