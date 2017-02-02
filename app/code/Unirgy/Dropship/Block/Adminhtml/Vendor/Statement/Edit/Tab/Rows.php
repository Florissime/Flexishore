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
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Grid;
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor\Statement;

class Rows extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        DropshipHelperData $helperData,
        Registry $registry,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('statement_rows');
        $this->setDefaultSort('row_id');
        $this->setUseAjax(true);
    }

    public function getStatement()
    {
        $statement = $this->_registry->registry('statement_data');
        if (!$statement) {
            /** @var \Unirgy\Dropship\Model\Vendor\Statement $statement */
            $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')->load($this->getStatementId());
            $this->_registry->register('statement_data', $statement);
        }
        return $statement;
    }

    protected function _prepareCollection()
    {
        /** @var \Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\Row\Collection $collection */
        $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement\Row')->getCollection()
            ->addFieldToFilter('statement_id', $this->getStatement()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        /*
        $this->addColumn('row_id', array(
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'row_id'
        ));
        */
        $this->addColumn('subtotal', array(
            'header'    => __('Subtotal'),
            'index' => 'subtotal',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        $this->addColumn('com_amount', array(
            'header'    => __('Com Amount'),
            'index' => 'com_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        $this->addColumn('trans_fee', array(
            'header'    => __('Trans Fee'),
            'index' => 'trans_fee',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        if ($this->getStatement()->getVendor()->getStatementTaxInPayout() != 'exclude_hide') {
        	$this->addColumn('tax', array(
                'header'    => __('Tax'),
                'index' => 'tax',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
            ));
        }
    	if ($this->getStatement()->getVendor()->getStatementShippingInPayout() != 'exclude_hide') {
        	$this->addColumn('shipping', array(
                'header'    => __('Shipping'),
                'index' => 'shipping',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
            ));
        }
        if ($this->getStatement()->getVendor()->getStatementDiscountInPayout() != 'exclude_hide') {
            $this->addColumn('discount', array(
                'header'    => __('Discount'),
                'index' => 'discount',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
            ));
        }
        $this->addColumn('adj_amount', array(
            'header'    => __('Adjustment'),
            'index' => 'adj_amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        if (!$this->_hlp->isStatementAsInvoice()) {
            $this->addColumn('total_payout', array(
                'header'    => __('Total Payout'),
                'index' => 'total_payout',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
            ));
            if ($this->_hlp->isUdpayoutActive()) {
                $this->addColumn('paid', array(
                    'header'    => __('Paid'),
                    'index' => 'paid',
                    'type' => 'options',
                    'options' => $this->_hlp->src()->setPath('yesno')->toOptionHash(),
                ));
            }
        } else {
            $this->addColumn('total_invoice', array(
                'header'    => __('Total Invoice'),
                'index' => 'total_invoice',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
            ));
        }
        $this->addColumn('order_increment_id', array(
            'header'    => __('Order ID'),
            'index'     => 'order_increment_id'
        ));
        $this->addColumn('order_created_at', array(
            'header'    => __('Order Date'),
            'index'     => 'order_created_at'
        ));
        $this->addColumn('po_increment_id', array(
            'header'    => __('PO ID'),
            'index'     => 'po_increment_id'
        ));
        $this->addColumn('po_created_at', array(
            'header'    => __('PO Date'),
            'index'     => 'po_created_at'
        ));
        $this->addColumn('po_statement_date', array(
            'header'    => __('PO Ready Date'),
            'index'     => 'po_statement_date'
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/rowGrid', array('_current'=>true));
    }

    public function getTabLabel()
    {
        return __('Rows');
    }
    public function getTabTitle()
    {
        return __('Rows');
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
