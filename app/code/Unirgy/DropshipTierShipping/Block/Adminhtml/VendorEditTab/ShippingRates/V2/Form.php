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
 * @package    Unirgy_DropshipTierShipping
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source;
use Unirgy\DropshipTierShipping\Helper\Data as DropshipTierShippingHelperData;
use Unirgy\DropshipTierShipping\Model\Source as ModelSource;

class Form extends Generic
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var ModelSource
     */
    protected $_tsSrc;

    /**
     * @var DropshipTierShippingHelperData
     */
    protected $_tsHlp;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        HelperData $helperData,
        Source $modelSource,
        ModelSource $dropshipTierShippingModelSource,
        DropshipTierShippingHelperData $dropshipTierShippingHelperData,
        array $data = []
    ) {
        $this->_hlp = $helperData;
        $this->_src = $modelSource;
        $this->_tsSrc = $dropshipTierShippingModelSource;
        $this->_tsHlp = $dropshipTierShippingHelperData;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('vendor_tiership');
    }

    protected function _prepareForm()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('tiership', [
            'legend' => __('Rates Definition')
        ]);

        $fieldset->addType('tiership_use_v2_rates', 'Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\DependSelect');

        $fieldset->addField('tiership_use_v2_rates', 'tiership_use_v2_rates', [
            'name' => 'tiership_use_v2_rates',
            'label' => __('Use Vendor Specific Rates'),
            'options' => $this->_src->setPath('yesno')->toOptionHash(),
            'field_config' => [
                'depend_fields' => [
                    'tiership_delivery_type_selector' => '1',
                    'tiership_v2_rates' => '1',
                    'tiership_v2_simple_rates' => '1',
                    'tiership_v2_simple_cond_rates' => '1',
                ]
            ]
        ]);

        $fieldset->addType('tiership_delivery_type_selector',
                           'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form\DeliveryTypeSelector');

        $fieldset->addField('tiership_delivery_type_selector', 'tiership_delivery_type_selector', [
            'name' => 'tiership_delivery_type_selector',
            'label' => __('Select Delivery Type To Setup Rates'),
            'options' => $this->_tsSrc->setPath('tiership_delivery_type_selector')->toOptionHash(),
        ]);

        if ($this->_tsHlp->isV2SimpleRates()) {

            $fieldset->addType(
                'tiership_v2_simple_rates',
                'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form\SimpleRates');

            $fieldset->addField('tiership_v2_simple_rates', 'tiership_v2_simple_rates', [
                'name' => 'tiership_v2_simple_rates',
                'label' => __('Rates'),
            ]);

        } elseif ($this->_tsHlp->isV2SimpleConditionalRates()) {

            $fieldset->addType(
                'tiership_v2_simple_cond_rates',
                'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form\SimpleCondRates');

            $fieldset->addField('tiership_v2_simple_cond_rates', 'tiership_v2_simple_cond_rates', [
                'name' => 'tiership_v2_simple_cond_rates',
                'label' => __('Rates'),
            ]);

        } else {

            $fieldset->addType(
                'tiership_v2_rates',
                'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\V2\Form\Rates');

            $fieldset->addField('tiership_v2_rates', 'tiership_v2_rates', [
                'name' => 'tiership_v2_rates',
                'label' => __('Rates'),
            ]);

        }

        if ($vendor) {
            $form->setValues($vendor->getData());
        }

        return parent::_prepareForm();
    }

}
