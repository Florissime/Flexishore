<?php

namespace Unirgy\DropshipVendorRatings\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Vendor extends Template
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(Context $context, 
        HelperData $helperData, 
        ReviewFactory $modelReviewFactory, 
        DropshipHelperData $dropshipHelperData,
        array $data = [])
    {
        $this->_rateHlp = $helperData;
        $this->_reviewFactory = $modelReviewFactory;
        $this->_hlp = $dropshipHelperData;

        parent::__construct($context, $data);
    }

    protected $_availableTemplates = [
        'default' => 'unirgy/ratings/vendor/summary.phtml',
        'short'   => 'unirgy/ratings/vendor/summary_short.phtml'
    ];

    public function getSummaryHtml($vendor, $templateType, $displayIfNoReviews)
    {
        // pick template among available
        if (empty($this->_availableTemplates[$templateType])) {
            $templateType = 'default';
        }
        $this->setTemplate($this->_availableTemplates[$templateType]);

        $this->setDisplayIfEmpty($displayIfNoReviews);

        $this->setVendor($vendor);
        $vendor = $this->getVendor();
        if (!$vendor->getRatingSummary()) {
            $this->_rateHlp->useMyEt();
            $this->_reviewFactory->create()->getEntitySummary($vendor, $this->_storeManager->getStore()->getId());
            $this->_rateHlp->resetEt();
        }

        return $this->toHtml();
    }

    public function getRatingSummary()
    {
        return $this->getVendor()->getRatingSummary()->getRatingSummary();
    }

    public function getReviewsCount()
    {
        return $this->getVendor()->getRatingSummary()->getReviewsCount();
    }

    public function setVendor($vendor)
    {
        $this->setData('vendor', $this->_hlp->getVendor($vendor));
        return $this;
    }

    public function getReviewsUrl()
    {
        return $this->_urlBuilder->getUrl('udratings/vendor/index', [
           'id'        => $this->getVendor()->getId(),
        ]);
    }
    public function addTemplate($type, $template)
    {
        $this->_availableTemplates[$type] = $template;
    }
}
