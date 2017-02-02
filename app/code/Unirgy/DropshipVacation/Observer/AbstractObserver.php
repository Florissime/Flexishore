<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Date;
use Unirgy\DropshipVacation\Helper\Data as HelperData;
use Unirgy\DropshipVacation\Model\Source;
use Unirgy\Dropship\Model\VendorFactory;

abstract class AbstractObserver
{
    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var HelperData
     */
    protected $_vacHlp;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        VendorFactory $modelVendorFactory,
        HelperData $helperData,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Locale\Resolver $localeResolver
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_vendorFactory = $modelVendorFactory;
        $this->_vacHlp = $helperData;
        $this->_localeDate = $localeDate;
        $this->_localeResolver = $localeResolver;

    }
}