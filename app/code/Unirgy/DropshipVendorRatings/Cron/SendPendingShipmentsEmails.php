<?php

namespace Unirgy\DropshipVendorRatings\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Magento\Store\Model\ScopeInterface;

class SendPendingShipmentsEmails
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_rateHlp;
    protected $_customerFactory;

    public function __construct(
        DropshipHelperData $udropshipHelper,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Unirgy\DropshipVendorRatings\Helper\Data $udratingHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_rateHlp = $udratingHelper;
        $this->_customerFactory = $customerFactory;
    }

    public function execute()
    {
        $daysFilter = $this->_scopeConfig->getValue('udropship/vendor_rating/notify_in_days', ScopeInterface::SCOPE_STORE);
        $pendingShipments = $this->_hlp->createObj('\Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection')
            ->addNotificationDaysFilter($daysFilter)
            ->addPendingFilter();
        $customerIds = $pendingShipments->getCustomerIds();
        foreach ($customerIds as $customerId) {
            $customer = $this->_customerFactory->create()->load($customerId);
            if ($customer->getId()) {
                $this->_rateHlp->sendPendingReviewEmail($customer);
            }
        }
    }
}