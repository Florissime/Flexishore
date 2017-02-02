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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\NewStatement;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Form as DataForm;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Form extends \Unirgy\Dropship\Block\Adminhtml\Widget\Form
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $hlp = $this->_hlp;
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('vendor_form', array(
            'legend'=>__('Statements Generation Criteria')
        ));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('all_vendors', 'select', array(
            'name'      => 'all_vendors',
            'label'     => __('Vendor Selection'),
            'class'     => 'required-entry',
            'required'  => true,
            'type'      => 'options',
            'options'   => array(
                1 => __('All Active Vendors'),
                0 => __('Selected Vendors'),
            ),
        ));
        
        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            $fieldset->addField('vendor_ids', 'udropship_vendor', array(
                'name'      => 'vendor_ids[]',
                'label'     => __('Vendors'),
            ));
        } else {
            $fieldset->addField('vendor_ids', 'multiselect', array(
                'name'      => 'vendor_ids[]',
                'label'     => __('Vendors'),
                'values'   => $this->_hlp->src()->setPath('vendors')->toOptionArray(),
            ));
        }

        $dateFormatIso = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField('date_from', 'date', array(
            'name'   => 'date_from',
            'label'  => __('Orders From Date'),
            'title'  => __('Orders From Date'),
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ));
        $fieldset->addField('date_to', 'date', array(
            'name'   => 'date_to',
            'label'  => __('Orders To Date'),
            'title'  => __('Orders To Date'),
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('use_locale_timezone', 'select', array(
            'name'      => 'use_locale_timezone',
            'label'     => __('Use Locale Timezone'),
            'type'      => 'options',
            'options'   => array(
                1 => __('Yes'),
                0 => __('No'),
            ),
        ));

        $fieldset->addField('statement_period', 'text', array(
            'name'      => 'statement_period',
            'label'     => __('Statement Period'),
            'note'      => __('If empty, will take YYMM of "Orders From Date"'),
        ));

        $this->getForm()->setValues(array(
            'all_vendors' => 1,
        ));

        return parent::_prepareForm();
    }
    
}