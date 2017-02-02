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
use \Unirgy\Dropship\Model\Vendor\Statement;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Adjustments extends \Magento\Backend\Block\Widget\Grid\Extended implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        Registry $registry,
        DropshipHelperData $helperData,
        Context $context,
        HelperData $backendHelper, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_registry = $registry;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('statement_adjustment');
        $this->setDefaultSort('id');
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
        /** @var \Unirgy\Dropship\Model\ResourceModel\Vendor\Statement\Adjustment\Collection $collection */
        $collection = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement\Adjustment')->getCollection()
            ->addFieldToFilter('statement_id', $this->getStatement()->getStatementId());
        ;

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'id'
        ));
        $this->addColumn('adjustment_id', array(
            'header'    => __('Adjustment ID'),
            'sortable'  => true,
        	'width'     => '300',
            'index'     => 'adjustment_id'
        ));
        $this->addColumn('po_id', array(
            'header'    => __('PO ID'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'po_id'
        ));
        $this->addColumn('po_type', array(
            'header'    => __('PO Type'),
            'sortable'  => true,
        	'width'     => '100',
            'index'     => 'po_type'
        ));
        $this->addColumn('amount', array(
            'header' => __('Amount'),
            'index' => 'amount',
            'type'  => 'price',
            'currency' => 'base_currency_code',
            'currency_code' => $this->_scopeConfig->getValue('currency/options/base'),
        ));
        $this->addColumn('username', array(
            'header'    => __('Username'),
            'sortable'  => true,
        	'width'     => '150',
            'index'     => 'username'
        ));
        $this->addColumn('comment', array(
            'header'    => __('Comment'),
            'index'     => 'comment'
        ));
        $this->addColumn('created_at', array(
            'header'    => __('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => 150,
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/adjustmentGrid', array('_current'=>true));
    }

    public function getTabLabel()
    {
        return __('Adjustments');
    }
    public function getTabTitle()
    {
        return __('Adjustments');
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
