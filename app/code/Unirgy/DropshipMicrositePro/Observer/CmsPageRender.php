<?php

namespace Unirgy\DropshipMicrositePro\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class CmsPageRender extends AbstractObserver implements ObserverInterface
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    public function __construct(
        HelperData $helperData,
        DropshipHelperData $dropshipHelperData,
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->_msHlp = $helperData;
        $this->_hlp = $dropshipHelperData;
        $this->_layout = $layout;
    }

    public function execute(Observer $observer)
    {
        if ($observer->getControllerAction()->getRequest()->getFullActionName()=='umicrosite_index_landingPage'
            && ($_vendor = $this->_msHlp->getCurrentVendor())
        ) {
            $landingPageTitle = $this->_msHlp->getLandingPageTitle();
            $observer->getPage()->setContentHeading(
                $landingPageTitle
            );
            $observer->getPage()->setTitle(
                $landingPageTitle
            );
            $observer->getPage()->setVendorLandingPageTitle(
                $landingPageTitle
            );
            $reviewsSummaryHtml = '';
            if ($this->_hlp->isModuleActive('Unirgy_DropshipVendorRatings')) {
                $reviewsSummaryHtml = $this->_hlp->getObj('Unirgy\DropshipVendorRatings\Helper\Data')->getReviewsSummaryHtml($_vendor);
                $observer->getPage()->setVendorReviewsSummaryHtml(
                    $reviewsSummaryHtml
                );
            }
            $contentHeadingBlock = $this->_layout->getBlock('page_content_heading');
            if ($contentHeadingBlock) {
                $contentHeadingBlock->setVendorLandingPageTitle(
                    $landingPageTitle
                );
                $contentHeadingBlock->setVendorReviewsSummaryHtml(
                    $reviewsSummaryHtml
                );
            }
        }
    }
}
