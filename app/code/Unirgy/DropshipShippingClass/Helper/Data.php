<?php

namespace Unirgy\DropshipShippingClass\Helper;

use Magento\Customer\Model\Session;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\DropshipShippingClass\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Unirgy\DropshipShippingClass\Model\ResourceModel\Vendor\Collection as VendorCollection;

class Data extends AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var RegionCollectionFactory
     */
    protected $_regionCollectionFactory;

    /**
     * @var CustomerCollection
     */
    protected $_customerCollection;

    /**
     * @var VendorCollection
     */
    protected $_vendorCollection;

    /**
     * @var Session
     */
    protected $_customerSession;

    public function __construct(
        Context $context,
        HelperData $helper,
        RegionCollectionFactory $regionCollectionFactory,
        CustomerCollection $customerCollection,
        VendorCollection $vendorCollection,
        Session $customerSession
    ) {
        $this->_helperData = $helper;
        $this->_regionCollectionFactory = $regionCollectionFactory;
        $this->_customerCollection = $customerCollection;
        $this->_vendorCollection = $vendorCollection;
        $this->_customerSession = $customerSession;

        parent::__construct($context);
    }

    public function getCustomerShipClass($address = null)
    {
        return $this->_getCustomerShipClass($address, false);
    }

    public function getAllCustomerShipClass($address = null)
    {
        return $this->_getCustomerShipClass($address, true);
    }

    protected function _getCustomerShipClass($address = null, $all = false)
    {
        $shipClass = $all ? [] : -1;
//        $cSess = ObjectManager::getInstance()->get('Magento\Customer\Model\Session');
        $cSess = $this->_customerSession;
        if (null == $address && $cSess->isLoggedIn()) {
            $address = $cSess->getCustomer()->getDefaultShippingAddress();
        }
        if ($address) {
            foreach ($this->getSortedCustomerShipClasses() as $cShipClass) {
                if (!$cShipClass->getRows()) {
                    if ($all) {
                        $shipClass[] = $cShipClass->getId();
                    } else {
                        $shipClass = $cShipClass->getId();
                        break;
                    }
                }
                foreach ($cShipClass->getRows() as $row) {
                    if ($address->getCountryId() == $row['country_id']
                        && $this->_checkRegion($address, $row)
                        && $this->_helperData->isZipcodeMatch($address->getPostcode(), $row['postcode'])
                    ) {
                        if ($all) {
                            $shipClass[] = $cShipClass->getId();
                        } else {
                            $shipClass = $cShipClass->getId();
                            break 2;
                        }
                    }
                }
            }
        }
        if ($all) {
            $shipClass[] = -1;
            $shipClass[] = '*';
        }
        return $shipClass;
    }

    public function getVendorShipClass($vendor = null)
    {
        return $this->_getVendorShipClass($vendor, false);
    }

    public function getAllVendorShipClass($vendor = null)
    {
        return $this->_getVendorShipClass($vendor, true);
    }

    protected function _getVendorShipClass($vendor, $all = false)
    {
        $shipClass = $all ? [] : -1;
        $vendor = $this->_helperData->getVendor($vendor);
        foreach ($this->getSortedVendorShipClasses() as $vShipClass) {
            if (!$vShipClass->getRows()) {
                if ($all) {
                    $shipClass[] = $vShipClass->getId();
                } else {
                    $shipClass = $vShipClass->getId();
                    break;
                }
            }
            foreach ($vShipClass->getRows() as $row) {
                if ($vendor->getCountryId() == $row['country_id']
                    && $this->_checkRegion($vendor, $row)
                    && $this->_helperData->isZipcodeMatch($vendor->getZip(), $row['postcode'])
                ) {
                    if ($all) {
                        $shipClass[] = $vShipClass->getId();
                    } else {
                        $shipClass = $vShipClass->getId();
                        break 2;
                    }
                }
            }
        }
        if ($all) {
            $shipClass[] = -1;
            $shipClass[] = '*';
        }
        return $shipClass;
    }

    protected function _checkRegion($obj1, $row)
    {
        $regionIds = explode(',', $row['region_id']);
        $regionIds = array_filter($regionIds);
        if (empty($regionIds)) return true;
        $rFilterKey = 'main_table.region_id';
        $regions = $this->_regionCollectionFactory->create()
            ->addCountryFilter($row['country_id'])
            ->addFieldToFilter($rFilterKey, ['in' => $regionIds]);
        if ($regions->count() == 0 || $regions->getItemById($obj1->getRegionId())) return true;
        return false;
    }

    public function processShipClass($shipping, $field, $serialize = false)
    {
        $shipClass = $shipping->getData($field);
        if ($serialize) {
            if (is_array($shipClass)) {
                $shipClass = array_filter($shipClass);
                $shipClass = implode(',', $shipClass);
            }
        } else {
            if (is_string($shipClass)) {
                $shipClass = explode(',', $shipClass);
            }
            if (!is_array($shipClass)) {
                $shipClass = [];
            }
            $shipClass = array_filter($shipClass);
        }
        $shipping->setData($field, $shipClass);
    }

    protected $_sortedCustomerShipClasses;

    public function getSortedCustomerShipClasses()
    {
        if (null == $this->_sortedCustomerShipClasses) {
            $this->_sortedCustomerShipClasses = $this->_customerCollection->addSortOrder();
        }
        return $this->_sortedCustomerShipClasses;
    }

    protected $_sortedVendorShipClasses;

    public function getSortedVendorShipClasses()
    {
        if (null == $this->_sortedVendorShipClasses) {
            $this->_sortedVendorShipClasses = $this->_vendorCollection->addSortOrder();
        }
        return $this->_sortedVendorShipClasses;
    }
}
