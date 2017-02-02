<?php

namespace Unirgy\DropshipVendorRatings\Observer;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as DropshipVendorRatingsHelperData;
use Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection;
use Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tabs;
use Unirgy\Dropship\Helper\Data as HelperData;

class UdropshipAdminhtmlVendorTabsAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $_request;
    protected $_authorization;

    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Unirgy\Dropship\Helper\Data $helperData,
        ScopeConfigInterface $configScopeConfigInterface,
        CustomerFactory $modelCustomerFactory,
        DropshipVendorRatingsHelperData $dropshipVendorRatingsHelperData, 
        RequestInterface $appRequestInterface
    )
    {
        $this->_request = $appRequestInterface;
        $this->_authorization = $authorization;

        parent::__construct($helperData, $configScopeConfigInterface, $modelCustomerFactory, $dropshipVendorRatingsHelperData);
    }

    public function execute(Observer $observer)
    {
        $block = $observer->getBlock();
        if (!$block instanceof Tabs
            || !$this->_request->getParam('id', 0)
        ) {
            return;
        }

        if ($block instanceof Tabs) {
            if ($this->_authorization->isAllowed('Unirgy_DropshipVendorRatings::review_all')) {
                $block->addTab('udratings', [
                    'label'     => __('Customer Reviews'),
                    'class'     => 'ajax',
                    'url'       => $block->getUrl('udratings/review/vendorReviews', ['_current' => true]),
                    'after'     => 'products_section'
                ]);
            }
        }
    }
}
