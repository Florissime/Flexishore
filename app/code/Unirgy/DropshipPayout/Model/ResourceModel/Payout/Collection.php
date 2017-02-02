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
 
namespace Unirgy\DropshipPayout\Model\ResourceModel\Payout;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Helper\Data as DropshipPayoutHelperData;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\DropshipPayout\Model\PayoutFactory;
use Unirgy\Dropship\Helper\Data as HelperData;

class Collection extends AbstractCollection
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipPayoutHelperData
     */
    protected $_payoutHlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    public function __construct(EntityFactoryInterface $entityFactory, 
        LoggerInterface $logger, 
        FetchStrategyInterface $fetchStrategy, 
        ManagerInterface $eventManager, 
        HelperData $helperData, 
        DropshipPayoutHelperData $dropshipPayoutHelperData, 
        ScopeConfigInterface $configScopeConfigInterface,
        PayoutFactory $modelPayoutFactory,
        AdapterInterface $connection = null, 
        AbstractDb $resource = null)
    {
        $this->_hlp = $helperData;
        $this->_payoutHlp = $dropshipPayoutHelperData;
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_payoutFactory = $modelPayoutFactory;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    protected $_payouts = [];
    protected $_dateFrom;
    protected $_dateTo;

    protected function _construct()
    {
        $this->_init('Unirgy\DropshipPayout\Model\Payout', 'Unirgy\DropshipPayout\Model\ResourceModel\Payout');
        parent::_construct();
    }
    
    public function setDateFrom($date)
    {
        $this->_dateFrom = $date;
        return $this;
    }
    public function setDateTo($date)
    {
        $this->_dateTo = $date;
        return $this;
    }
    
    public function resetPayouts()
    {
        $this->_payouts = [];
    }
    
    public function loadStatementPayouts($statement, $pos)
    {
        $conn = $this->getConnection();
        $sSelect = $this->getSelect()
            ->join(['pr' => $this->getTable('udropship_payout_row')], 'main_table.payout_id=pr.payout_id', [])
            ->where('pr.po_id in (?)', $pos->getAllIds())
            ->where('payout_status!=?', Payout::STATUS_CANCELED);
        $pSelect = $conn->select()
            ->from($this->getTable('udropship_payout'))
            ->where('statement_id=?', $statement->getStatementId())
            ->where('payout_status!=?', Payout::STATUS_CANCELED);
        $this->_select = $conn->select()->union(["($sSelect)", "($pSelect)"]);

        return $this;
    }
    
    public function loadScheduledPayouts()
    {
        $hlp = $this->_hlp;
        $ptHhlp = $this->_payoutHlp;

        // find all scheduled payouts scheduled for earlier than now, sorted by schedule time
        $this->addFieldToFilter('payout_status', 'scheduled')
            ->addFieldToFilter('scheduled_at', ['datetime'=>true, 'to'=>$this->_hlp->now()]);
        $this->getSelect()->order('scheduled_at');

        // preprocess payouts and set correct statuses
        foreach ($this->getItems() as $p) {
            $this->addPayout($p, true);
        }

        $this->cleanPayouts();

        return $this;
    }
    
    public function cleanPayouts()
    {
        foreach($this->getItems() as $p) {
            if ($p->getPayoutStatus()!='processing') {
                $this->removePayout($p);
            }
        }
        return $this;
    }

    public function addExternalPayout($payout, $validate=false)
    {
        $this->_setIsLoaded();
        $this->addItem($payout);
        return $this->_addPayout($payout, $validate);
    }
    
    public function addPayout($payout, $validate=false)
    {
        $payout->setPayoutStatus('processing');
        return $this->_addPayout($payout, $validate);
    }
    
    protected function _addPayout($payout, $validate=false)
    {
        $vId = $payout->getVendorId();
        if ($validate) {
            // if vendors are not configured to be scheduled anymore, mark as canceled
            if (!$this->_payoutHlp->isVendorEnabled($this->_hlp->getVendor($vId), true)) {
                $payout->setPayoutStatus('canceled')->save();
                return $this;
            }
            // if multiple payouts for the same vendor exist, mark older payouts as missed
            elseif (!empty($this->_payouts[$vId])) {
                $this->_payouts[$vId]->delete();
            }
        }
        $this->_payouts[$vId] = $payout;
        return $this;
    }

    public function removePayout($payout)
    {
        $this->removeItemByKey($payout->getId());
        return $this;
    }

    public function addPendingPos($vendorIds=null)
    {
        if ($vendorIds===true) {
            $vendorIds = array_keys($this->_payouts);
        }
        $hlp = $this->_hlp;
        $ptHlp = $this->_payoutHlp;
        foreach ($vendorIds as $vId) {
            $ptPoStatuses = $hlp->getVendor($vId)->getPayoutPoStatus();
            if (!is_array($ptPoStatuses)) {
                $ptPoStatuses = explode(',', $ptPoStatuses);
            }
            $poType = $hlp->getVendor($vId)->getStatementPoType();
            $this->getResource()->fixStatementDate($hlp->getVendor($vId), $poType, $ptPoStatuses, $this->_dateFrom, $this->_dateTo);
            $res = $this->_hlp->rHlp();
            if ($poType == 'po') {
                $pos = $this->_hlp->createObj('Unirgy\DropshipPo\Model\ResourceModel\Po\GridCollection');
            } else {
                $pos = $this->_hlp->createObj('Unirgy\Dropship\Model\ResourceModel\ShipmentGridCollection');
            }
            $pos->getSelect()->join(
                ['t'=>$poType == 'po' ? $res->getTableName('udropship_po') : $res->getTableName('sales_shipment')],
                't.entity_id=main_table.entity_id'
            );
            $pos->addAttributeToFilter('main_table.udropship_payout_status', ['null'=>true]);
            $pos->addAttributeToSort('main_table.entity_id', 'asc');
            $pos->addAttributeToFilter('t.udropship_vendor', $vId);
            $pos->addAttributeToFilter('t.udropship_status', ['in'=>$ptPoStatuses]);
            if (isset($this->_dateFrom)) {
                $pos->getSelect()
                    ->where("t.statement_date IS NOT NULL")
                    ->where("t.statement_date!='0000-00-00 00:00:00'")
                    ->where("t.statement_date>=?", $this->_dateFrom)
                    ->where("t.statement_date<=?", $this->_dateTo);
            }

            $this->_payoutFactory->create()->processPos($pos, $hlp->getVendor($vId)->getStatementSubtotalBase());
            
            foreach ($pos as $po) {
                $this->addPo($po);
            }
        }

        return $this;
    }
    
    public function finishPayout()
    {
        foreach ($this as $payout) {
            $payout->finishPayout();
        }
        return $this;
    }
    
    public function save()
    {
        foreach ($this->getItems() as $item) {
            try {
                $item->save()->setIsJustSaved(true);
            } catch (\Exception $e) {
                $this->_logLoggerInterface->error($e);
            }
        }
        return $this;
    }
    
    public function saveOrdersPayouts($deleteEmpty=false)
    {
        foreach ($this->getItems() as $item) {
            if (count($item->getOrders())==0) {
                $this->removePayout($item);
                if ($deleteEmpty) $item->delete();
            } else {
                $item->save();
            }
        }
        return $this;
    }
    
    public function pay()
    {
        $ptHlp = $this->_payoutHlp;
        $ptPerMethod = [];
        $ptMethods = [];
        foreach ($this as $pt) {
            if ($pt->getPayoutStatus() == Payout::STATUS_PAID) { 
                $pt->addMessage(
                    __("This payout already paid")
                );
                $pt->save();
                continue;
            }
            if ($pt->getPayoutStatus() == Payout::STATUS_CANCELED) { 
                $pt->addMessage(
                    __("This payout is canceled")
                );
                $pt->save();
                continue;
            }
            if ($pt->getPayoutStatus() == Payout::STATUS_PAYPAL_IPN) { 
                $pt->addMessage(
                    __("This payout wait paypal IPN")
                );
                $pt->save();
                continue;
            }
            if ($pt->getTotalDue()<=0) {
                $pt->addMessage(
                    __('Payout "total due" must be positive'),
                    Payout::STATUS_ERROR
                );
                $pt->save();
                continue;
            }
            if (!$pt->getPayoutMethod()) {
                $pt->addMessage(
                    __('Empty payout method'),
                    Payout::STATUS_ERROR
                );
                $pt->save();
                continue;
            }
            if (!isset($ptMethods[$pt->getPayoutMethod()])) {
                $pmNode = $this->_hlp->config()->getPayoutMethod($pt->getPayoutMethod());
                $methodClass = $pmNode['model'];
                if (!class_exists($methodClass)) {
                    $pt->addMessage(
                        __("Can't find payout method class"),
                        Payout::STATUS_ERROR
                    );
                    $pt->save();
                    continue;
                }
                $ptMethods[$pt->getPayoutMethod()] = $this->_hlp->createObj($methodClass);
            }
            $ptPerMethod[$pt->getPayoutMethod()][] = $pt;
        }
        foreach ($ptMethods as $ptMethodId => $ptMethod) {
            try {
                if ($this->getFlag('skip_offline') && !$ptMethod->isOnline()) continue;
                $ptMethod->pay($ptPerMethod[$ptMethodId]);
                foreach ($ptPerMethod[$ptMethodId] as $pt) {
                    if ($pt->hasPayoutMethodErrors()) {
                        $pt->addMessage(
                            implode("\n", $pt->PayoutMethodErrors()), 
                            Payout::STATUS_ERROR
                        );
                        $pt->save();
                    } else {
                        if (!$this->_scopeConfig->isSetFlag('udropship/payout_paypal/use_ipn', ScopeInterface::SCOPE_STORE)) {
                            $pt->afterPay();
                        } else {
                            $pt->addMessage(__('Successfully send payment. Waiting for IPN to complete.'), Payout::STATUS_PAYPAL_IPN)->setIsJustPaid(true);
                        }
                        $pt->save();
                    }
                }
            } catch (\Exception $e) {
                foreach ($ptPerMethod[$ptMethodId] as $pt) {
                    $pt->addMessage($e->getMessage(), Payout::STATUS_ERROR)->save();
                }
                $this->_logLoggerInterface->error($e);
            }
        }
        return $this;
    }

    public function addPo($po)
    {
        $vId = $po->getUdropshipVendor();
        if (empty($this->_payouts[$vId])) {
            $payout = false;
            foreach ($this->getItems() as $item) {
                if ($item->getVendorId()==$vId
                    && $item->getPayoutStatus()=='processing'
                ) {
                    $payout = $item;
                    break;
                }
            }
            if (!$payout) {
                $payout = $this->_payoutFactory->create()->setVendorId($vId);
                $this->addItem($payout);
            }
            $this->_payouts[$vId] = $payout;
        } else {
            $payout = $this->_payouts[$vId];
        }
        $payout->addPo($po);
        return $this;
    }

}
