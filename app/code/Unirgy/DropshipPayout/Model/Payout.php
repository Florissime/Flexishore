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

namespace Unirgy\DropshipPayout\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Tax\Helper\Data as TaxHelperData;
use Unirgy\DropshipPayout\Helper\Data as DropshipPayoutHelperData;
use Unirgy\DropshipPayout\Helper\ProtectedCode;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source as DropshipSource;
use Unirgy\Dropship\Model\Vendor\StatementFactory;
use Unirgy\Dropship\Model\Vendor\Statement\AbstractStatement;
use Unirgy\Dropship\Model\Vendor\Statement\StatementInterface;

class Payout extends AbstractStatement implements StatementInterface
{
    /**
     * @var StatementFactory
     */
    protected $_statementFactory;

    /**
     * @var DropshipPayoutHelperData
     */
    protected $_payoutHlp;

    /**
     * @var ProtectedCode
     */
    protected $_payoutHlpPr;

    public function __construct(
        HelperData $helperData,
        TaxHelperData $taxHelperData,
        DropshipSource $source,
        Context $context, 
        Registry $registry, 
        StatementFactory $vendorStatementFactory, 
        DropshipPayoutHelperData $dropshipPayoutHelperData,
        ProtectedCode $helperProtectedCode,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null, 
        array $data = [])
    {
        $this->_statementFactory = $vendorStatementFactory;
        $this->_payoutHlp = $dropshipPayoutHelperData;
        $this->_payoutHlpPr = $helperProtectedCode;

        parent::__construct($helperData, $taxHelperData, $source, $context, $registry, $resource, $resourceCollection, $data);
    }

    protected $_eventPrefix = 'udpayout_payout';
    protected $_eventObject = 'payout';
    
    const TYPE_AUTO      = 'auto';
    const TYPE_MANUAL    = 'manual';
    const TYPE_SCHEDULED = 'scheduled';
    const TYPE_STATEMENT = 'statement';
    
    const STATUS_PENDING    = 'pending';
    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_HOLD       = 'hold';
    const STATUS_PAYPAL_IPN = 'paypal_ipn';
    const STATUS_PAID       = 'paid';
    const STATUS_ERROR      = 'error';
    const STATUS_CANCELED   = 'canceled';

    protected function _construct()
    {
        $this->_init('Unirgy\DropshipPayout\Model\ResourceModel\Payout');
    }
    
    public function getAdjustmentPrefix()
    {
        return $this->_hlp->getAdjustmentPrefix('payout');
    }
    
    public function isMyAdjustment($adjustment)
    {
        return 0 === strpos($adjustment->getAdjustmentId(), $this->getAdjustmentPrefix())
            || 0 === strpos($adjustment->getAdjustmentId(), $this->_hlp->getAdjustmentPrefix('statement:payout'));
    }
    
    protected $_statement;
    public function getStatement()
    {
        if (is_null($this->_statement)) {
            $this->_statement = $this->_statementFactory->create()->load($this->getStatementId(), 'statement_id');
        }
        return $this->_statement;
    }
    public function setStatement($statement)
    {
        $this->_statement = $statement;
        return $this;
    }

    public function calculateOrder($order)
    {
        if ($this->_hlp->isModuleActive('Unirgy_DropshipTierCommission')) {
            return $this->_calculateOrderTierCom($order);
        } else {
            return $this->_calculateOrder($order);
        }
    }

    protected function _calculateOrderTierCom($order)
    {
        /** @var \Magento\Tax\Helper\Data $taxHelper */
        $taxHelper = $this->_hlp->createObj('\Magento\Tax\Helper\Data');
        $taxInSubtotal = $taxHelper->displaySalesBothPrices() || $taxHelper->displaySalesPriceInclTax();
        if (is_null($order['com_percent'])) {
            $order['com_percent'] = $this->getVendor()->getCommissionPercent();
        }
        $order['com_percent'] *= 1;
        if (is_null($order['po_com_percent'])) {
            $order['po_com_percent'] = $this->getVendor()->getCommissionPercent();
        }
        $order['po_com_percent'] *= 1;

        if (isset($order['amounts']['tax']) && in_array($this->getVendor()->getStatementTaxInPayout(),
                ['', 'include'])
        ) {
            if ($taxInSubtotal) {
                if ($this->getVendor()->getApplyCommissionOnTax()) {
                    $order['amounts']['subtotal'] += $order['amounts']['tax'];
                    $order['amounts']['subtotal'] += $order['amounts']['hidden_tax'];
                    $order['amounts']['com_amount'] = $order['amounts']['subtotal'] * $order['com_percent'] / 100;
                } else {
                    $order['amounts']['com_amount'] = $order['amounts']['subtotal'] * $order['com_percent'] / 100;
                    $order['amounts']['subtotal'] += $order['amounts']['tax'];
                    $order['amounts']['subtotal'] += $order['amounts']['hidden_tax'];
                }
            } else {
                $order['amounts']['com_amount'] = $order['amounts']['subtotal'] * $order['com_percent'] / 100;
                $order['amounts']['total_payout'] += $order['amounts']['tax'];
                $order['amounts']['total_payout'] += $order['amounts']['hidden_tax'];
                $order['amounts']['total_payment'] += $order['amounts']['tax'];
                $order['amounts']['total_payment'] += $order['amounts']['hidden_tax'];
                if ($this->getVendor()->getApplyCommissionOnTax()) {
                    $taxCom = round($order['amounts']['tax'] * $order['com_percent'] / 100, 2);
                    $order['amounts']['com_amount'] += $taxCom;
                    $order['amounts']['total_payout'] -= $taxCom;
                }
            }
        } else {
            $order['amounts']['com_amount'] = $order['amounts']['subtotal'] * $order['com_percent'] / 100;
        }

        $order['amounts']['com_amount'] = round($order['amounts']['com_amount'], 2);

        $order['amounts']['total_payout'] = $order['amounts']['subtotal'] - $order['amounts']['com_amount'] - $order['amounts']['trans_fee'] + $order['amounts']['adj_amount'];
        $order['amounts']['total_payment'] = $order['amounts']['subtotal'] + $order['amounts']['adj_amount'];

        if (isset($order['amounts']['discount']) && in_array($this->getVendor()->getStatementDiscountInPayout(),
                ['', 'include'])
        ) {
            if ($this->getVendor()->getApplyCommissionOnDiscount()) {
                $discountCom = round($order['amounts']['discount'] * $order['com_percent'] / 100, 2);
                $order['amounts']['com_amount'] -= $discountCom;
                $order['amounts']['total_payout'] += $discountCom;
            }
            $order['amounts']['total_payout'] -= $order['amounts']['discount'];
        }
        $order['amounts']['total_payment'] -= $order['amounts']['discount'];

        if (isset($order['amounts']['shipping']) && in_array($this->getVendor()->getStatementShippingInPayout(),
                ['', 'include'])
        ) {
            if ($this->getVendor()->getApplyCommissionOnShipping()) {
                $shipCom = round($order['amounts']['shipping'] * $order['po_com_percent'] / 100, 2);
                $order['amounts']['com_amount'] += $shipCom;
                $order['amounts']['total_payout'] -= $shipCom;
            }
            $order['amounts']['total_payout'] += $order['amounts']['shipping'];
        }
        $order['amounts']['total_payment'] += $order['amounts']['shipping'];
        $order['amounts']['total_invoice'] = $order['amounts']['com_amount'] + $order['amounts']['trans_fee'] + $order['amounts']['adj_amount'];

        return $order;
    }

    public function addPo($po)
    {
        if ($this->_hlp->isModuleActive('Unirgy_DropshipTierCommission')) {
            return $this->_addPoTierCom($po);
        } else {
            return $this->_addPo($po);
        }
    }

    protected function _addPoTierCom($po)
    {
        $hlp = $this->_hlp;

        $this->initTotals();

        $hlp->collectPoAdjustments([$po]);
        $hlp->addVendorSkus($po);

        $onlySubtotal = false;
        foreach ($po->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy(true)) continue;
            $order = $this->initPoItem($item, $onlySubtotal);
            $onlySubtotal = true;

            $this->_eventManager->dispatch('udropship_vendor_payout_item_row', [
                'payout' => $this,
                'po' => $po,
                'po_item' => $item,
                'order' => &$order
            ]);

            $order = $this->calculateOrder($order);
            $this->_totals_amount = $this->accumulateOrder($order, $this->_totals_amount);

            $poId = $po->getId() ? $po->getId() : spl_object_hash($po);
            $this->_orders[$poId . '-' . $item->getId()] = $order;
        }

        return $this;
    }

    public function initPoItem($poItem, $onlySubtotal)
    {
        $po = $poItem->getPo() ? $poItem->getPo() : $poItem->getShipment();
        $orderItem = $poItem->getOrderItem();
        $hlp = $this->_hlp;
        $order = [
            'po_id' => $po->getId(),
            'date' => $hlp->getPoOrderCreatedAt($po),
            'id' => $hlp->getPoOrderIncrementId($po),
            'po_com_percent' => $po->getCommissionPercent(),
            'com_percent' => $poItem->getCommissionPercent(),
            'trans_fee' => $poItem->getTransactionFee(),
            'adjustments' => $onlySubtotal ? [] : $po->getAdjustments(),
            'order_id' => $po->getOrderId(),
            'order_created_at' => $hlp->getPoOrderCreatedAt($po),
            'order_increment_id' => $hlp->getPoOrderIncrementId($po),
            'po_increment_id' => $po->getIncrementId(),
            'po_created_at' => $po->getCreatedAt(),
            'po_statement_date' => $po->getStatementDate(),
            'po_type' => $po instanceof Po ? 'po' : 'shipment',
            'sku' => $poItem->getSku(),
            'simple_sku' => $poItem->getOrderItem()->getProductOptionByCode('simple_sku'),
            'vendor_sku' => $poItem->getVendorSku(),
            'vendor_simple_sku' => $poItem->getVendorSimpleSku(),
            'product' => $poItem->getName(),
            'po_item_id' => $poItem->getId()
        ];
        if ($this->getVendor()->getStatementSubtotalBase() == 'cost') {
            if (abs($poItem->getBaseCost()) > 0.001) {
                $subtotal = $poItem->getBaseCost() * $poItem->getQty();
            } else {
                $subtotal = $orderItem->getBaseCost() * $poItem->getQty();
            }
        } else {
            $subtotal = $orderItem->getBasePrice() * $poItem->getQty();
        }

        $qtyOrdered = $orderItem->getQtyOrdered();
        $_rowDivider = $poItem->getQty() / ($qtyOrdered > 0 ? $qtyOrdered : 1);
        $iHiddenTax = $orderItem->getBaseHiddenTaxAmount() * ($_rowDivider > 0 ? $_rowDivider : 1);
        $iTax = $orderItem->getBaseTaxAmount() * ($_rowDivider > 0 ? $_rowDivider : 1);
        $iDiscount = $orderItem->getBaseDiscountAmount() * ($_rowDivider > 0 ? $_rowDivider : 1);

        if ($po->getOrder()->getData('udpo_amount_fields') && $poItem->getPo()
            || $po->getOrder()->getData('ud_amount_fields') && $poItem->getShipment()
        ) {
            $iHiddenTax = $poItem->getBaseHiddenTaxAmount();
            $iTax = $poItem->getBaseTaxAmount();
            $iDiscount = $poItem->getBaseDiscountAmount();
            $subtotal = $poItem->getBaseRowTotal();
        }

        $shippingAmount = $po->getBaseShippingAmount();
        if ($this->getVendor()->getIsShippingTaxInShipping()) {
            $shippingAmount += $po->getBaseShippingTax();
        } else {
            if ($onlySubtotal) {
                $iTax += $po->getBaseShippingTax();
            }
        }
        $iTax = $this->_deltaRound($iTax, $po->getId());
        $amountRow = [
            'subtotal' => $subtotal,
            'shipping' => $onlySubtotal ? 0 : $shippingAmount,
            'tax' => $iTax,
            'hidden_tax' => $iHiddenTax,
            'discount' => $iDiscount,
            'handling' => $onlySubtotal ? 0 : $po->getBaseHandlingFee(),
            'trans_fee' => $onlySubtotal ? 0 : $po->getTransactionFee(),
            'adj_amount' => $onlySubtotal ? 0 : $po->getAdjustmentAmount(),
        ];
        foreach ($amountRow as &$_ar) {
            $_ar = is_null($_ar) ? 0 : $_ar;
        }
        unset($_ar);
        $order['amounts'] = array_merge($this->_getEmptyTotals(), $amountRow);
        return $order;
    }

    protected $_roundingDeltas = [];

    protected function _deltaRound($price, $id)
    {
        if ($price) {
            $delta = isset($this->_roundingDeltas[$id]) ? $this->_roundingDeltas[$id] : 0;
            $price += $delta;
            $this->_roundingDeltas[$id] = $price - round($price, 2);
            $price = round($price, 2);
        }
        return $price;
    }

    protected function _compactTotals()
    {
        parent::_compactTotals();
        if ($this->_hlp->isModuleActive('Unirgy_DropshipTierCommission')) {
            $ordersCnt = [];
            foreach ($this->getOrders() as $order) {
                $ordersCnt[$order['po_id']] = 1;
            }
            $this->setTotalOrders(array_sum($ordersCnt));
        }
        return $this;
    }

    protected function _addPo($po)
    {
        $hlp = $this->_hlp;
        $ptHlp = $this->_payoutHlp;
        $vendor = $this->getVendor();

        $this->initTotals();

        $hlp->collectPoAdjustments([$po]);
        
        $sId = $po->getId();
        $order = $this->initOrder($po);
    
        $this->_eventManager->dispatch('udropship_vendor_payout_row', [
            'payout'   => $this,
            'po' => $po,
            'order'    => &$order
        ]);
        
        $order = $this->calculateOrder($order);
        $this->_totals_amount = $this->accumulateOrder($order, $this->_totals_amount);

        $poId = $po->getId() ? $po->getId() : spl_object_hash($po);
        $this->_orders[$poId] = $order;

        return $this;
    }
    
    public function finishPayout()
    {
        return $this->finishStatement();
    }

    protected function _getEmptyTotals($format=false)
    {
        return $this->_payoutHlp->getEmptyPayoutTotals($format);
    }
    
    protected function _getEmptyCalcTotals($format=false)
    {
        return $this->_payoutHlp->getEmptyPayoutCalcTotals($format);
    }
    
    public function getAdjustmentClass()
    {
        if (is_null($this->_adjustmentClass)) {
            $this->_adjustmentClass = '\Unirgy\DropshipPayout\Model\Payout\Adjustment';
        }
        return $this->_adjustmentClass;
    }
    
    public function pay()
    {
        $this->_payoutHlpPr->payoutPay($this);
        return $this;
    }
    
    public function afterPay()
    {
        $this->markDueAmountsPaid($this);
        $this->addMessage(__('Successfully paid'), self::STATUS_PAID)->setIsJustPaid(true);
        $this->initTotals();
        foreach ($this->_orders as &$order) {
            $order['paid'] = true;
        }
        unset($order);
        if ($this->getPayoutType() == self::TYPE_STATEMENT
            && ($statement = $this->_statementFactory->create()->load($this->getStatementId(), 'statement_id'))
            && $statement->getId()
        ) {
            $statement->completePayout($this);
        }
        return $this;
    }
    
    public function addMessage($message, $status=null)
    {
        $ei = sprintf("%s\n[%s] %s",
            $this->getErrorInfo(),
            $this->_hlp->now(),
            $message
        );
        $this->setErrorInfo(ltrim($ei));
        if (!empty($status)
            && $this->getPayoutStatus() != self::STATUS_PAID
            && ($this->getPayoutStatus() != self::STATUS_PAYPAL_IPN || $status == self::STATUS_PAID)
        ) {
            $this->setPayoutStatus($status);
        }
        return $this;
    }
    
    public function setPayoutStatus($status)
    {
        if ($status==self::STATUS_HOLD) {
            $this->setData('before_hold_status', $this->getPayoutStatus());
        }
        return $this->setData('payout_status', $status);
    }
    
    public function cancel()
    {
        $this->setCleanPayoutFlag(true)->setPayoutStatus(self::STATUS_CANCELED)->save();
        return $this;
    }

    public function getMethodInstance()
    {
        $pmNode = $this->_hlp->config()->getPayoutMethod($this->getPayoutMethod());
        if (!$pmNode) {
            return false;
        }
        $methodClass = $pmNode['model'];
        if (!class_exists($methodClass)) {
            return false;
        }
        return $this->_hlp->createObj($methodClass);
    }
}
