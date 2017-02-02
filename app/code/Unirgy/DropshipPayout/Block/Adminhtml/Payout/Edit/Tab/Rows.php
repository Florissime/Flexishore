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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipPayout\Model\PayoutFactory;
use Unirgy\DropshipPayout\Model\Payout\RowFactory;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Rows extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var RowFactory
     */
    protected $_payoutRowFactory;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(Context $context, 
        HelperData $backendHelper, 
        Registry $frameworkRegistry, 
        PayoutFactory $modelPayoutFactory, 
        RowFactory $payoutRowFactory, 
        DropshipHelperData $helperData,
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_payoutRowFactory = $payoutRowFactory;
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('udpayout_payout_rows');
        $this->setDefaultSort('row_id');
        $this->setUseAjax(true);
    }

    public function getPayout()
    {
        $payout = $this->_coreRegistry->registry('payout_data');
        if (!$payout) {
            $payout = $this->_payoutFactory->create()->load($this->getPayoutId());
            $this->_coreRegistry->register('payout_data', $payout);
        }
        return $payout;
    }

    protected function _prepareCollection()
    {
        $collection = $this->_payoutRowFactory->create()->getCollection()
            ->addFieldToFilter('payout_id', $this->getPayout()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if ($this->_hlp->isModuleActive('Unirgy_DropshipTierCommission')) {
            $this->addColumn('sku', [
                'header'    => __('SKU'),
                'index'     => 'sku'
            ]);
            $this->addColumn('vendor_sku', [
                'header'    => __('Vendor SKU'),
                'index'     => 'vendor_sku'
            ]);
            $this->addColumn('product', [
                'header'    => __('Product'),
                'index'     => 'product'
            ]);
            $this->addColumnsOrder('sku', 'po_increment_id');
            $this->addColumnsOrder('sku', 'vendor_sku');
            $this->addColumnsOrder('product', 'sku');
        }
        /*
        $this->addColumn('row_id', [
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'row_id'
        ]);
        */
        $this->addColumn('order_increment_id', [
            'header'    => __('Order ID'),
            'index'     => 'order_increment_id'
        ]);
        $this->addColumn('order_created_at', [
            'header'    => __('Order Date'),
            'index'     => 'order_created_at'
        ]);
        $this->addColumn('po_increment_id', [
            'header'    => __('PO ID'),
            'index'     => 'po_increment_id'
        ]);
        $this->addColumn('po_created_at', [
            'header'    => __('PO Date'),
            'index'     => 'po_created_at'
        ]);
        $this->addColumn('po_statement_date', [
            'header'    => __('PO Ready Date'),
            'index'     => 'po_statement_date'
        ]);
        $this->addColumn('subtotal', [
            'header'    => __('Subtotal'),
            'index' => 'subtotal',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
        ]);
        $this->addColumn('com_amount', [
            'header'    => __('Com Amount'),
            'index' => 'com_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
        ]);
        $this->addColumn('trans_fee', [
            'header'    => __('Trans Fee'),
            'index' => 'trans_fee',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
        ]);
    	if ($this->getPayout()->getVendor()->getStatementTaxInPayout() != 'exclude_hide') {
        	$this->addColumn('tax', [
                'header'    => __('Tax'),
                'index' => 'tax',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }
    	if ($this->getPayout()->getVendor()->getStatementShippingInPayout() != 'exclude_hide') {
        	$this->addColumn('shipping', [
                'header'    => __('Shipping'),
                'index' => 'shipping',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }
        if ($this->getPayout()->getVendor()->getStatementDiscountInPayout() != 'exclude_hide') {
            $this->addColumn('discount', [
                'header'    => __('Discount'),
                'index' => 'discount',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }
        $this->addColumn('adj_amount', [
            'header'    => __('Adjustment'),
            'index' => 'adj_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
        ]);
        if (!$this->_hlp->isStatementAsInvoice()) {
            $this->addColumn('total_payout', [
                'header'    => __('Total Payout'),
                'index' => 'total_payout',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        } else {
            $this->addColumn('total_payment', [
                'header'    => __('Total Payment'),
                'index' => 'total_payment',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/rowGrid', ['_current'=>true]);
    }

    public function getTabLabel()
    {
        return $this->getData('label');
    }
    public function getTabTitle()
    {
        return $this->getData('title');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }
}
