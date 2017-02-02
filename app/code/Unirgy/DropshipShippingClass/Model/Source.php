<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
 * Currently not in use
 */
namespace Unirgy\DropshipShippingClass\Model;

use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;
use Unirgy\DropshipShippingClass\Helper\Data as DropshipShippingClassHelperData;
use Unirgy\DropshipShippingClass\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Unirgy\DropshipShippingClass\Model\ResourceModel\Vendor\Collection as VendorCollection;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var DropshipShippingClassHelperData
     */
    protected $_dropshipShippingClassHelperData;

    protected $_customerCollection;

    protected $_vendorCollection;

    public function __construct(
        HelperData $helperData,
        CustomerCollection $customerCollection,
        VendorCollection $vendorCollection,
        DropshipShippingClassHelperData $dropshipShippingClassHelperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;
        $this->_dropshipShippingClassHelperData = $dropshipShippingClassHelperData;
        $this->_customerCollection = $customerCollection;
        $this->_vendorCollection = $vendorCollection;

        parent::__construct($data);
    }

    const VENDOR_SHIP_CLASS_US = 1;

    const VENDOR_SHIP_CLASS_INT = 2;

    const CUSTOMER_SHIP_CLASS_US = 1;

    const CUSTOMER_SHIP_CLASS_INT = 2;

    public function toOptionHash($selector = false)
    {
        $hlp = $this->_helperData;
        $hlpv = $this->_dropshipShippingClassHelperData;

        switch ($this->getPath()) {

            case 'vendor_ship_class':
                $options = $this->_vendorCollection->toOptionHash();
                $options[-1] = __('* Other Vendor');
                break;

            case 'customer_ship_class':
                $options = $this->_customerCollection->toOptionHash();
                $options[-1] = __('* Other Customer');
                break;

            default:
                throw new \Exception(__('Invalid request for source options: ' . $this->getPath()));
        }

        if ($selector) {
            $options = ['' => __('* Please select')] + $options;
        }

        return $options;
    }
}
