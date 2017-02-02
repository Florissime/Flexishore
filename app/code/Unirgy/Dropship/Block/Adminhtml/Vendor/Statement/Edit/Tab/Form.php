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

use \Magento\Backend\Block\Widget\Form as WidgetForm;
use \Magento\Framework\Data\Form as DataForm;
use \Magento\Framework\Filter\Sprintf;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;

class Form extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        Registry $registry,
        HelperData $helper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_registry = $registry;
        $this->_hlp = $helper;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('statement_form');
    }

    protected function _prepareForm()
    {
        $statement = $this->_registry->registry('statement_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('statement_form', array(
            'legend'=>__('Statement Info')
        ));

        $fieldset->addField('pay_flag', 'hidden', array(
            'name'      => 'pay_flag',
        ));
        
        $fieldset->addField('refresh_flag', 'hidden', array(
            'name'      => 'refresh_flag',
        ));
        
        $fieldset->addField('vendor_id', 'note', array(
            'name'      => 'vendor_id',
            'label'     => __('Vendor'),
            'text'      => $this->_hlp->src()->setPath('vendors')->getOptionLabel($statement->getVendorId()),
        ));
        
        $fieldset->addField('statement_id', 'note', array(
            'name'      => 'statement_id',
            'label'     => __('Statement ID'),
            'text'      => $statement->getStatementId(),
        ));
        
        $fieldset->addField('po_type', 'select', array(
            'name'      => 'po_type',
            'label'     => __('Po Type'),
            'disabled'  => true,
            'options'   => $this->_hlp->src()->setPath('statement_po_type')->toOptionHash(),
        ));

        $fieldset->addField('total_orders', 'note', array(
            'name'      => 'total_orders',
            'label'     => __('Number of Orders'),
            'text'      => $statement->getData('total_orders')
        ));

        if (!$hlp->isStatementAsInvoice()) {
            $fieldset->addField('total_payout', 'note', array(
                'name'      => 'total_payout',
                'label'     => __('Total Payout'),
                'text'      => $this->_hlp->formatPrice($statement->getData('total_payout'))
            ));

            if ($this->_hlp->isUdpayoutActive()) {
                $fieldset->addField('total_paid', 'note', array(
                    'name'      => 'total_paid',
                    'label'     => __('Total Paid'),
                    'text'      => $this->_hlp->formatPrice($statement->getData('total_paid'))
                ));

                $fieldset->addField('total_due', 'note', array(
                    'name'      => 'total_due',
                    'label'     => __('Total Due'),
                    'text'      => $this->_hlp->formatPrice($statement->getData('total_due'))
                ));
            }
        } else {
            $fieldset->addField('total_invoice', 'note', array(
                'name'      => 'total_invoice',
                'label'     => __('Total Invoice'),
                'text'      => $this->_hlp->formatPrice($statement->getData('total_invoice'))
            ));
        }
        
        $fieldset->addField('notes', 'textarea', array(
            'name'      => 'notes',
            'label'     => __('Notes'),
        ));
        
        $fieldset->addField('adjustment', 'text', array(
            'name'      => 'adjustment',
            'label'     => __('Adjustment'),
            'value_filter' => new Sprintf('%s', 2),
        ))
        ->setRenderer(
            $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Renderer\Adjustment')->setStatement($statement)
        );
        
        if ($statement) {
            $form->setValues($statement->getData());
        }

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Statement Information');
    }
    public function getTabTitle()
    {
        return __('Statement Information');
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
