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

namespace Unirgy\DropshipPayout\Block\Adminhtml\Payout;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipPayout\Model\PayoutFactory;
use Unirgy\DropshipPayout\Model\Source as ModelSource;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Model\Source;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var ModelSource
     */
    protected $_payoutSrc;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(Context $context,
        HelperData $backendHelper, 
        PayoutFactory $modelPayoutFactory, 
        Source $modelSource, 
        ModelSource $dropshipPayoutModelSource, 
        DropshipHelperData $helperData, 
        array $data = [])
    {
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_src = $modelSource;
        $this->_payoutSrc = $dropshipPayoutModelSource;
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('payoutGrid');
        $this->setDefaultSort('payout_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('payout_filter');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_payoutFactory->create()->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('payout_id', [
            'header'    => __('ID'),
            'index'     => 'payout_id',
            'width'     => 10,
            'type'      => 'number',

        ]);
        
        $this->addColumn('statement_id', [
            'header'    => __('Statement ID'),
            'index'     => 'statement_id',
        ]);

        $this->addColumn('vendor_id', [
            'header' => __('Vendor'),
            'index' => 'vendor_id',
            'type' => 'options',
            'options' => $this->_src->setPath('vendors')->toOptionHash(),
            'filter' => '\Unirgy\Dropship\Block\Vendor\GridColumnFilter'
        ]);

        $this->addColumn('payout_type', [
            'header' => __('Payout Type'),
            'index' => 'payout_type',
            'type' => 'options',
            'options' => $this->_payoutSrc->setPath('payout_type_internal')->toOptionHash(),
        ]);

        $this->addColumn('payout_status', [
            'header' => __('Payout Status'),
            'index' => 'payout_status',
            'type' => 'options',
            'options' => $this->_payoutSrc->setPath('payout_status')->toOptionHash(),
        ]);

        $this->addColumn('transaction_id', [
            'header'    => __('Transaction ID'),
            'index'     => 'transaction_id',
        ]);

        if ($this->_hlp->isModuleActive('Unirgy_DropshipPaypalAdaptive')) {
            $this->addColumn('sender_transaction_id', [
                'header'    => __('Sender Transaction ID'),
                'index'     => 'sender_transaction_id',
            ]);
        }
        
        $this->addColumn('total_orders', [
            'header'    => __('# of Orders'),
            'index'     => 'total_orders',
            'type'      => 'number',
        ]);

        if (!$this->_hlp->isStatementAsInvoice()) {
            $this->addColumn('total_payout', [
                'header' => __('Total Payout'),
                'index' => 'total_payout',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);

            $this->addColumn('total_paid', [
                'header' => __('Total Paid'),
                'index' => 'total_paid',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);

            $this->addColumn('total_due', [
                'header' => __('Total Due'),
                'index' => 'total_due',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        } else {
            $this->addColumn('total_payout', [
                'header' => __('Total Payment'),
                'index' => 'total_payment',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);

            $this->addColumn('total_paid', [
                'header' => __('Payment Paid'),
                'index' => 'payment_paid',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);

            $this->addColumn('total_due', [
                'header' => __('Payment Due'),
                'index' => 'payment_due',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }

        $this->addColumn('created_at', [
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);

        $this->addColumn('updated_at', [
            'header'    => __('Updated At'),
            'index'     => 'updated_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);

        $this->addColumn('scheduled_at', [
            'header'    => __('Scheduled At'),
            'index'     => 'scheduled_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXml', __('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('payout_id');
        $this->getMassactionBlock()->setFormFieldName('payout');

        $this->getMassactionBlock()->addItem('pay', [
             'label'=> __('Pay'),
             'url'  => $this->getUrl('*/*/massPay'),
             'confirm' => __('Are you sure?')
        ]);
        
        $this->getMassactionBlock()->addItem('delete', [
             'label'=> __('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => __('Are you sure?')
        ]);

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current'=>true]);
    }
}
