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
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source;

class FormNew extends \Unirgy\Dropship\Block\Adminhtml\Widget\Form
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var Source
     */
    protected $_src;

    public function __construct(
        Source $modelSource,
        Context $context,
        Registry $registry, 
        FormFactory $formFactory, 
        array $data = [])
    {
        $this->_src = $modelSource;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('payout_form_new');
    }

    protected function _prepareForm()
    {
        $payout = $this->_coreRegistry->registry('payout_data');
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('payout_form', [
            'legend'=>__('Payout Info')
        ]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField('all_vendors', 'select', [
            'name'      => 'all_vendors',
            'label'     => __('Vendor Selection'),
            'class'     => 'required-entry',
            'required'  => true,
            'type'      => 'options',
            'options'   => [
                1 => __('All Active Vendors'),
                0 => __('Selected Vendors'),
            ],
        ]);

        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect', ScopeInterface::SCOPE_STORE)) {
            $fieldset->addField('vendor_ids', 'udropship_vendor', [
                'name'      => 'vendor_ids[]',
                'label'     => __('Vendors'),
            ]);
        } else {
            $fieldset->addField('vendor_ids', 'multiselect', [
                'name'      => 'vendor_ids[]',
                'label'     => __('Vendors'),
                'values'   => $this->_src->setPath('vendors')->toOptionArray(),
            ]);
        }

        $dateFormatIso = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField('date_from', 'date', [
            'name'   => 'date_from',
            'label'  => __('Orders From Date'),
            'title'  => __('Orders From Date'),
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ]);
        $fieldset->addField('date_to', 'date', [
            'name'   => 'date_to',
            'label'  => __('Orders To Date'),
            'title'  => __('Orders To Date'),
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ]);
        $fieldset->addField('use_locale_timezone', 'select', [
            'name'      => 'use_locale_timezone',
            'label'     => __('Use Locale Timezone'),
            'type'      => 'options',
            'options'   => [
                1 => __('Yes'),
                0 => __('No'),
            ],
        ]);

        $fieldset->addField('notes', 'textarea', [
            'name'      => 'notes',
            'label'     => __('Notes'),
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
