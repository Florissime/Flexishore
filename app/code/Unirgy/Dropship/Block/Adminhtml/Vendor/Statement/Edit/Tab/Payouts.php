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
use \Magento\Backend\Helper\Data as HelperData;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor\Statement;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Payouts extends \Magento\Backend\Block\Widget\Grid\Extended
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
        $this->_hlp = $helperData;
        $this->_registry = $registry;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('statement_payouts');
        $this->setDefaultSort('payout_id');
        $this->setUseAjax(true);
    }

    public function getStatement()
    {
        $statement = $this->_registry->registry('statement_data');
        if (!$statement) {
            $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')->load($this->getStatementId());
            $this->_registry->register('statement_data', $statement);
        }
        return $statement;
    }

    protected function _prepareCollection()
    {
        $collection = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\Payout')->getCollection()
            ->addFieldToFilter('statement_id', $this->getStatement()->getStatementId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('payout_id', array(
            'header'    => __('Payout ID'),
            'index'     => 'payout_id',
        ));
        
        $this->addColumn('statement_id', array(
            'header'    => __('Statement ID'),
            'index'     => 'statement_id',
        ));

        $this->addColumn('vendor_id', array(
            'header' => __('Vendor'),
            'index' => 'vendor_id',
            'type' => 'options',
            'options' => $this->_hlp->src()->setPath('vendors')->toOptionHash(),
            'filter' => '\Unirgy\Dropship\Block\Vendor\GridColumnFilter'
        ));

        $this->addColumn('payout_type', array(
            'header' => __('Payout Type'),
            'index' => 'payout_type',
            'type' => 'options',
            'options' => $this->_hlp->getObj('\Unirgy\DropshipPayout\Model\Source')->setPath('payout_type_internal')->toOptionHash(),
        ));

        $this->addColumn('payout_status', array(
            'header' => __('Payout Status'),
            'index' => 'payout_status',
            'type' => 'options',
            'options' => $this->_hlp->getObj('\Unirgy\DropshipPayout\Model\Source')->setPath('payout_status')->toOptionHash(),
        ));
        
        $this->addColumn('transaction_id', array(
            'header'    => __('Transaction ID'),
            'index'     => 'transaction_id',
        ));

        $this->addColumn('total_orders', array(
            'header'    => __('# of Orders'),
            'index'     => 'total_orders',
            'type'      => 'number',
        ));
        
        $this->addColumn('total_payout', array(
            'header' => __('Total Payout'),
            'index' => 'total_payout',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addColumn('total_paid', array(
            'header' => __('Total Paid'),
            'index' => 'total_paid',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        
        $this->addColumn('total_due', array(
            'header' => __('Total Due'),
            'index' => 'total_due',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));

        $this->addColumn('created_at', array(
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));

        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('udpayout/payout/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/payoutGrid', array('_current'=>true));
    }
}
