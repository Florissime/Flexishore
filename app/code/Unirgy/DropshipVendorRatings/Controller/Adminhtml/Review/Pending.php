<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class Pending extends AbstractReview
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    public function __construct(
        Registry $frameworkRegistry,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($context, $helperData, $udropshipHelper, $reviewFactory);
    }

    public function execute()
    {
        $this->_coreRegistry->register('usePendingFilter', true);
        $page = $this->_initAction();
        return $page->addContent($page->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Main'));
    }
}
