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

namespace Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Filter\Sprintf;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\DropshipPayout\Model\Source as ModelSource;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Model\Source;

class Form extends Generic
{
    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var ModelSource
     */
    protected $_payoutSrc;

    public function __construct(Context $context,
        Registry $registry, 
        FormFactory $formFactory, 
        DropshipHelperData $dropshipHelperData, 
        Source $modelSource, 
        ModelSource $dropshipPayoutModelSource, 
        array $data = [])
    {
        $this->_hlp = $dropshipHelperData;
        $this->_src = $modelSource;
        $this->_payoutSrc = $dropshipPayoutModelSource;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('payout_form');
    }

    protected function _prepareForm()
    {
        $payout = $this->_coreRegistry->registry('payout_data');
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $v = $this->_hlp->getVendor($payout->getVendorId());

        $fieldset = $form->addFieldset('payout_form', [
            'legend'=>__('Payout Info')
        ]);

        $fieldset->addField('pay_flag', 'hidden', [
            'name'      => 'pay_flag',
        ]);
        
        $fieldset->addField('vendor_id', 'note', [
            'name'      => 'vendor_id',
            'label'     => __('Vendor'),
            'text'      => sprintf('<a href="%s">%s</a>', $this->getUrl('adminhtml/udropshipadmin_vendor/edit', ['id'=>$payout->getVendorId()]), $this->_src->setPath('vendors')->getOptionLabel($payout->getVendorId())),
        ]);
        
        $fieldset->addField('statement_id', 'note', [
            'name'      => 'statement_id',
            'label'     => __('Statement ID'),
            'text'      => $payout->getStatement()->getId() 
                ? sprintf('<a href="%s">%s</a>', $this->getUrl('adminhtml/udropshipadmin_statement/edit', ['id'=>$payout->getStatement()->getId()]), $payout->getStatementId())
                : ''
        ]);

        $fieldset->addField('payout_type', 'select', [
            'name'      => 'payout_type',
            'label'     => __('Type'),
            'disabled'  => true,
            'options'   => $this->_payoutSrc->setPath('payout_type_internal')->toOptionHash(),
        ]);
        
        $fieldset->addField('payout_method', 'select', [
            'name'      => 'payout_method',
            'label'     => __('Method'),
            'disabled'  => true,
            'options'   => $this->_payoutSrc->setPath('payout_method')->toOptionHash(),
        ]);

        try {
            $method = $payout->getMethodInstance();
            if ($method && $method->hasExtraInfo($payout)) {
                $fieldset->addField('payout_method_details', 'note', [
                    'name'      => 'payout_method_details',
                    'label'     => __('Method Specific Details'),
                    'text'      => $method->getExtraInfoHtml($payout)
                ]);
            }
        } catch (\Exception $e) {}

        if ($v->getData('payout_details')) {
            $fieldset->addField('vendor_payout_details', 'note', [
                'name'      => 'vendor_payout_details',
                'label'     => __('Payout Additional Details'),
                'text'      => $this->escapeHtml($v->getData('payout_details'))
            ]);
        }
        
        $fieldset->addField('transaction_id', 'note', [
            'name'      => 'transaction_id',
            'label'     => __('Transaction ID'),
            'text'      => $payout->getData('transaction_id')
        ]);
        
        if ($payout->getData('payout_method') == 'paypal') {
            $fieldset->addField('paypal_correlation_id', 'note', [
                'name'      => 'transaction_id',
                'label'     => __('Paypal Correlation ID'),
                'text'      => $payout->getData('paypal_correlation_id')
            ]);
        }
        
        $fieldset->addField('payout_status', 'select', [
            'name'      => 'payout_status',
            'label'     => __('Status'),
            'disabled'  => true,
            'options'   => $this->_payoutSrc->setPath('payout_status')->toOptionHash(),
        ]);
        
        $fieldset->addField('po_type', 'select', [
            'name'      => 'po_type',
            'label'     => __('Po Type'),
            'disabled'  => true,
            'options'   => $this->_src->setPath('statement_po_type')->toOptionHash(),
        ]);

        $fieldset->addField('total_orders', 'note', [
            'name'      => 'total_orders',
            'label'     => __('Number of Orders'),
            'text'      => $payout->getData('total_orders')
        ]);
        
        $fieldset->addField('transaction_fee', 'note', [
            'name'      => 'transaction_fee',
            'label'     => __('Transaction Fee'),
            'text' => $this->_hlp->formatPrice($payout->getData('transaction_fee'))
        ]);

        if (!$this->_hlp->isStatementAsInvoice()) {
            $fieldset->addField('total_payout', 'note', [
                'name'      => 'total_payout',
                'label'     => __('Total Payout'),
                'text' => $this->_hlp->formatPrice($payout->getData('total_payout'))
            ]);

            $fieldset->addField('total_paid', 'note', [
                'name'      => 'total_paid',
                'label'     => __('Total Paid'),
                'text' => $this->_hlp->formatPrice($payout->getData('total_paid'))
            ]);

            $fieldset->addField('total_due', 'note', [
                'name'      => 'total_due',
                'label'     => __('Total Due'),
                'text' => $this->_hlp->formatPrice($payout->getData('total_due'))
            ]);
        } else {
            $fieldset->addField('total_payment', 'note', [
                'name'      => 'total_payment',
                'label'     => __('Total Payment'),
                'text' => $this->_hlp->formatPrice($payout->getData('total_payment'))
            ]);

            $fieldset->addField('payment_paid', 'note', [
                'name'      => 'payment_paid',
                'label'     => __('Payment Paid'),
                'text' => $this->_hlp->formatPrice($payout->getData('payment_paid'))
            ]);

            $fieldset->addField('payment_due', 'note', [
                'name'      => 'payment_due',
                'label'     => __('Payment Due'),
                'text' => $this->_hlp->formatPrice($payout->getData('payment_due'))
            ]);
        }

        $fieldset->addField('notes', 'textarea', [
            'name'      => 'notes',
            'label'     => __('Notes'),
        ]);
        
        if (!($payout->getPayoutStatus() == Payout::STATUS_PAID
                || $payout->getPayoutStatus() == Payout::STATUS_CANCELED
                || $payout->getPayoutStatus() == Payout::STATUS_HOLD)
        ) {
            $fieldset->addField('adjustment', 'text', [
                'name'      => 'adjustment',
                'label'     => __('Adjustment'),
                'value_filter' => new Sprintf('%s', 2),
            ])
            ->setRenderer(
                $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Renderer\Adjustment')->setStatement($payout)
            );
        }
        
        $fieldset->addField('error_info', 'note', [
            'name'      => 'error_info',
            'label'     => __('Messages'),
            'text'      => nl2br($payout->getErrorInfo())
        ]);

        if ($payout) {
            $form->setValues($payout->getData());
        }

        return parent::_prepareForm();
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
