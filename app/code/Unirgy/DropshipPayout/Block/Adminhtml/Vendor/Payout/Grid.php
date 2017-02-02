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

namespace Unirgy\DropshipPayout\Block\Adminhtml\Vendor\Payout;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as WidgetGrid;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipPayout\Model\PayoutFactory;
use Unirgy\DropshipPayout\Model\Source;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Model\VendorFactory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var Source
     */
    protected $_payoutSrc;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(Context $context,
        HelperData $backendHelper, 
        Registry $frameworkRegistry, 
        VendorFactory $modelVendorFactory, 
        PayoutFactory $modelPayoutFactory, 
        Source $modelSource, 
        DropshipHelperData $helperData, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_vendorFactory = $modelVendorFactory;
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_payoutSrc = $modelSource;
        $this->_hlp = $helperData;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('udpayout_vendor_payouts');
        $this->setDefaultSort('payout_id');
        $this->setUseAjax(true);
    }

    public function getVendor()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        if (!$vendor) {
            $vendor = $this->_vendorFactory->create()->load($this->getVendorId());
            $this->_coreRegistry->register('vendor_data', $vendor);
        }
        return $vendor;
    }

    protected function _prepareCollection()
    {
        $collection = $this->_payoutFactory->create()->getCollection()
            ->addFieldToFilter('vendor_id', $this->getVendor()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('pt_grid_payout_id', [
            'header'    => __('ID'),
            'index'     => 'payout_id',
            'width'     => 10,
            'type'      => 'number',
        ]);

        $this->addColumn('pt_grid_payout_type', [
            'header' => __('Payout Type'),
            'index' => 'payout_type',
            'type' => 'options',
            'options' => $this->_payoutSrc->setPath('payout_type')->toOptionHash(),
        ]);

        $this->addColumn('pt_grid_payout_status', [
            'header' => __('Payout Status'),
            'index' => 'payout_status',
            'type' => 'options',
            'options' => $this->_payoutSrc->setPath('payout_status')->toOptionHash(),
        ]);

        $this->addColumn('pt_grid_total_orders', [
            'header'    => __('# of Orders'),
            'index'     => 'total_orders',
            'type'      => 'number',
        ]);

        if (!$this->_hlp->isStatementAsInvoice()) {
            $this->addColumn('pt_grid_total_payout', [
                'header' => __('Total Payout'),
                'index' => 'total_payout',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        } else {
            $this->addColumn('pt_grid_total_payment', [
                'header' => __('Total Payment'),
                'index' => 'total_payment',
                'type'  => 'price',
                'currency' => 'base_currency_code',
                'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
            ]);
        }

        $this->addColumn('pt_grid_created_at', [
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);
        
        $this->addColumn('pt_grid_scheduled_at', [
            'header'    => __('Scheduled At'),
            'index'     => 'scheduled_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('udpayout/payout/edit', ['id' => $row->getId()]);
    }

    public function getGridUrl()
    {
        return $this->getUrl('udpayout/payout/vendorPayoutsGrid', ['_current'=>true]);
    }
}
