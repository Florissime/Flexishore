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
use Unirgy\DropshipPayout\Model\Payout\AdjustmentFactory;

class Adjustments extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
     * @var AdjustmentFactory
     */
    protected $_payoutAdjustmentFactory;

    public function __construct(Context $context,
        HelperData $backendHelper, 
        Registry $frameworkRegistry, 
        PayoutFactory $modelPayoutFactory, 
        AdjustmentFactory $payoutAdjustmentFactory, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_payoutAdjustmentFactory = $payoutAdjustmentFactory;

        parent::__construct($context, $backendHelper, $data);
        $this->setId('udpayout_payout_adjustment');
        $this->setDefaultSort('id');
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
        $collection = $this->_payoutAdjustmentFactory->create()->getCollection()
            ->addFieldToFilter('payout_id', $this->getPayout()->getId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'id'
        ]);
        $this->addColumn('adjustment_id', [
            'header'    => __('Adjustment ID'),
            'sortable'  => true,
        	'width'     => '300',
            'index'     => 'adjustment_id'
        ]);
        $this->addColumn('po_id', [
            'header'    => __('Po ID'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'po_id'
        ]);
        $this->addColumn('po_type', [
            'header'    => __('PO Type'),
            'sortable'  => true,
        	'width'     => '100',
            'index'     => 'po_type'
        ]);
        $this->addColumn('amount', [
            'header' => __('Amount'),
            'index' => 'amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base', ScopeInterface::SCOPE_STORE),
        ]);
        $this->addColumn('username', [
            'header'    => __('Username'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'username'
        ]);
        $this->addColumn('comment', [
            'header'    => __('Comment'),
            'index'     => 'comment'
        ]);
        $this->addColumn('created_at', [
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ]);
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/adjustmentGrid', ['_current'=>true]);
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
