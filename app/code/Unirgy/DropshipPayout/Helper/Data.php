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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
namespace Unirgy\DropshipPayout\Helper;

use Magento\Cron\Model\Observer;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\DropshipPayout\Model\PayoutFactory;
use Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Vendor;
use Unirgy\Dropship\Model\VendorFactory;

class Data extends AbstractHelper
{
    public $statementPayouts;
    public $statementPayoutsByPo = [];
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ScheduleFactory
     */
    protected $_scheduleFactory;

    public function __construct(Context $context, 
        HelperData $helperData, 
        PayoutFactory $modelPayoutFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        VendorFactory $modelVendorFactory, 
        ScheduleFactory $modelScheduleFactory)
    {
        $this->_hlp = $helperData;
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_timezone = $timezone;
        $this->_vendorFactory = $modelVendorFactory;
        $this->_scheduleFactory = $modelScheduleFactory;

        parent::__construct($context);
    }

    public function isVendorEnabled($vendor, $scheduled=false)
    {
        if (!$scheduled) {
            return $vendor->getData("payout_type");
        } else {
            $psCodes = $this->getPayoutSchedules('code2schedule');
            return $vendor->getData("payout_type")=='scheduled'
                && (array_key_exists($vendor->getData("payout_schedule_type"), $psCodes) || $vendor->getData("payout_schedule"));
        }
    }
    
    public function createPayout($vendor, $status='pending', $payoutType=null)
    {
        if (!$vendor instanceof Vendor) {
            $vendor = $this->_hlp->getVendor($vendor);
        }
        $payout = $this->_payoutFactory->create()->addData([
            'payout_type' => !is_null($payoutType) ? $payoutType : $vendor->getPayoutType(),
            'payout_method' => $vendor->getPayoutMethod(),
            'payout_status' => $status,
            'vendor_id' => $vendor->getId(),
            'po_type' => $vendor->getStatementPoType()
        ]);
        return $payout->initTotals();
    }
    
    public function processPost()
    {
        $r = $this->_request;
        $dateFrom = $r->getParam('date_from');
        $dateTo = $r->getParam('date_to');

        $datetimeFormatInt = \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT;
        $dateFormat = $this->_timezone->getDateFormat(\IntlDateFormatter::SHORT);
        if ($r->getParam('use_locale_timezone')) {
            $dateFrom = $this->_hlp->dateLocaleToInternal($dateFrom, $dateFormat, true);
            $dateTo = $this->_hlp->dateLocaleToInternal($dateTo, $dateFormat, true);
            $dateTo = $this->_timezone->date($dateTo, null, false);
            $dateTo->add(new \DateInterval('P1D'));
            $dateTo->sub(new \DateInterval('PT1S'));
            $dateTo = datefmt_format_object($dateTo, $datetimeFormatInt);
        } else {
            $dateFrom = $this->_timezone->date($dateFrom, null, false);
            $dateFrom = datefmt_format_object($dateFrom, $datetimeFormatInt);
            $dateTo = $this->_timezone->date($dateTo, null, false);
            $dateTo->add(new \DateInterval('P1D'));
            $dateTo->sub(new \DateInterval('PT1S'));
            $dateTo = datefmt_format_object($dateTo, $datetimeFormatInt);
        }

        if ($r->getParam('all_vendors')) {
            $vendors = $this->_vendorFactory->create()->getCollection()
                ->addFieldToFilter('status', 'A')
                ->getAllIds();
        } else {
            $vendors = $r->getParam('vendor_ids');
        }
        if (empty($vendors)) {
            throw new \Exception(__('Please select vendors'));
        }

        /** @var \Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection $payouts */
        $payouts = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection')
            ->setDateFrom($dateFrom)->setDateTo($dateTo);
        foreach ($vendors as $vId) {
            $payout = $this->createPayout(
                $vId, 
                Payout::STATUS_PENDING, 
                Payout::TYPE_MANUAL
            );
            $payout->setUseLocaleTimezone($r->getParam('use_locale_timezone'));
            $payout->setDateFrom($dateFrom)->setDateTo($dateTo);
            $payouts->addExternalPayout($payout, false);
        }
        $payouts->addPendingPos($vendors);
        $payouts->finishPayout()->saveOrdersPayouts();

        return $payouts;
    }

    public function getPayoutSchedules($hashType=false)
    {
        $ps = $this->scopeConfig->getValue('udropship/payout/payout_schedules', ScopeInterface::SCOPE_STORE);
        $ps = $this->_hlp->unserialize($ps);
        usort($ps, [$this->_hlp, 'sortBySortOrder']);
        $psCodes = [];
        if (is_array($ps)) {
            $psCodes = [];
            foreach ($ps as $_ps) {
                if ($hashType=='code2title') {
                    $psCodes[@$_ps['code']] = @$_ps['title'];
                } elseif ($hashType=='code2schedule') {
                    $psCodes[@$_ps['code']] = @$_ps['schedule'];
                } else {
                    $psCodes[] = $_ps;
                }
            }
        }
        return $psCodes;
    }
    
    public function generateSchedules()
    {
        $now = time();
        $scheduleAheadFor = $this->scopeConfig->getValue(\Magento\Cron\Observer\ProcessCronQueueObserver::XML_PATH_SCHEDULE_AHEAD_FOR, ScopeInterface::SCOPE_STORE)*60;
        $timeAhead = $now + $scheduleAheadFor;

        $exists = [];
        $scheduled = $this->_payoutFactory->create()->getCollection()
            ->addFieldToFilter('scheduled_at', ['datetime'=>true, 'from'=>strftime('%Y-%m-%d %H:%M:00', $now)]);
        foreach ($scheduled as $p) {
            $exists[$p->getVendorId().'/'.$p->getScheduledAt()] = 1;
        }

        $schedule = $this->_scheduleFactory->create();

        $vendors = $this->_vendorFactory->create()->getCollection();
        $psCodes = $this->getPayoutSchedules('code2schedule');
        $vendors->getSelect()->where("payout_type='scheduled'");
        if (empty($psCodes)) {
            $vendors->getSelect()->where("payout_schedule<>''");
        } else {
            $vendors->getSelect()->where("payout_schedule_type in (?) OR (payout_schedule<>'' AND payout_schedule is not null)", array_keys($psCodes));
        }

        foreach ($vendors as $vId=>$v) {
            $v->afterLoad();
            $payout = $this->createPayout(
                $vId,
                Payout::TYPE_SCHEDULED, 
                Payout::STATUS_SCHEDULED
            );

            try {
            if ($v->getData("payout_schedule_type") && array_key_exists($v->getData("payout_schedule_type"), $psCodes)) {
                $schedule->setCronExpr($psCodes[$v->getData("payout_schedule_type")]);
            } else {
                $schedule->setCronExpr($v->getData("payout_schedule"));
            }
            } catch (\Exception $e) {
                continue;
            }

            for ($time = $now; $time < $timeAhead; $time += 60) {
                $ts = strftime('%Y-%m-%d %H:%M:00', $time);
                if (empty($exists["{$vId}/{$ts}"]) && $schedule->trySchedule($time)) {
                    $payout->unsPayoutId()->setScheduledAt($ts)->save();
                }
            }
        }

        return $this;
    }
    
    public function cleanupSchedules()
    {
        return $this;
    }
    
    public function getEmptyPayoutOrder($format=false)
    {
        return $this->_hlp->getStatementEmptyOrderAmounts($format);
    }
    
    public function getEmptyPayoutTotals($format=false)
    {
        return $this->_hlp->getStatementEmptyTotalsAmount($format);
    }
    
    public function getEmptyCalcPayoutTotals($format=false)
    {
        return $this->_hlp->getStatementEmptyCalcTotalsAmount($format);
    }
}
