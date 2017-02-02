<?php

namespace Unirgy\DropshipVendorRatings\Observer;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as DropshipVendorRatingsHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;

abstract class AbstractObserver
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var DropshipVendorRatingsHelperData
     */
    protected $_rateHlp;

    public function __construct(
        HelperData $helperData,
        ScopeConfigInterface $configScopeConfigInterface, 
        CustomerFactory $modelCustomerFactory,
        DropshipVendorRatingsHelperData $dropshipVendorRatingsHelperData
    )
    {
        $this->_hlp = $helperData;
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_customerFactory = $modelCustomerFactory;
        $this->_rateHlp = $dropshipVendorRatingsHelperData;

    }

    protected function _sales_order_shipment_save_before($observer, $isStatusEvent)
    {
        $po = $observer->getEvent()->getShipment();
        if ($po->getUdropshipVendor()
            && ($vendor = $this->_hlp->getVendor($po->getUdropshipVendor()))
            && $vendor->getId()
            && (!$po->getUdratingDate() || $po->getUdratingDate() == '0000-00-00 00:00:00')
        ) {
            $readyStatuses = $this->_scopeConfig->getValue('udropship/vendor_rating/ready_status', ScopeInterface::SCOPE_STORE);
            if (!is_array($readyStatuses)) {
                $readyStatuses = explode(',', $readyStatuses);
            }
            if (in_array($po->getUdropshipStatus(), $readyStatuses)) {
                $po->setUdratingDate($this->_hlp->now());
                if ($isStatusEvent) {
                    $po->getResource()->saveAttribute($po, 'udrating_date');
                }
            }
        }
    }

}