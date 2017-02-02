<?php

namespace Unirgy\DropshipVacation\Cron;

use Unirgy\DropshipVacation\Helper\Data as DropshipVacationHelperData;
use Unirgy\Dropship\Model\VendorFactory;
use Unirgy\DropshipVacation\Model\Source;

class ProcessVacations
{
    /**
     * @var DropshipVacationHelperData
     */
    protected $_vacHlp;

    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    protected $_hlp;
    protected $_localeDate;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        DropshipVacationHelperData $dropshipVacationHelperData,
        VendorFactory $modelVendorFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_localeDate = $localeDate;
        $this->_vacHlp = $dropshipVacationHelperData;
        $this->_vendorFactory = $modelVendorFactory;

    }
    public function execute()
    {
        $vendors = $this->_vendorFactory->create()->getCollection();
        $vacDisabled = Source::MODE_VACATION_DISABLE;
        $vacNotify = Source::MODE_VACATION_NOTIFY;
        $vacNo = Source::MODE_NOT_VACATION;
        $vacationEnd = $this->_localeDate->scopeDate();
        $vendors->getSelect()->where("vacation_mode in (?)", [$vacDisabled,$vacNotify]);
        $vendors->getSelect()->where("vacation_end<?", $vacationEnd->format(\Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT));
        foreach ($vendors as $vendor) {
            $wasDisabled = $vendor->getData('vacation_mode')==$vacDisabled;
            $vendor->setData('vacation_mode', $vacNo);
            if ($wasDisabled) $this->_vacHlp->processVendorChange($vendor);
            $vendor->setData('vacation_end', '');
            $this->_hlp->rHlp()->updateModelFields($vendor, 'vacation_mode');
        }
    }
}