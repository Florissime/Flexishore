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

namespace Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Unirgy\Dropship\Helper\Data as HelperData;

class Form extends Generic
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        HelperData $helperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('vendor_tiership');
    }

    protected function _prepareForm()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('tiership', [
            'legend' => __('Rates Definition')
        ]);

        if ($this->_scopeConfig->getValue('carriers/udtiership/use_simple_rates',
                                          ScopeInterface::SCOPE_STORE)
        ) {

            $fieldset->addType('tiership_simple_rates',
                               'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Form\SimpleRates');

            $fieldset->addField('tiership_simple_rates', 'tiership_simple_rates', [
                'name' => 'tiership_simple_rates',
                'label' => __('Rates'),
            ]);

        } else {

            $fieldset->addType('tiership_rates',
                               'Unirgy\DropshipTierShipping\Block\Adminhtml\VendorEditTab\ShippingRates\Form\Rates');

            $fieldset->addField('tiership_rates', 'tiership_rates', [
                'name' => 'tiership_rates',
                'label' => __('Rates'),
            ]);

        }

        if ($vendor) {
            $form->setValues($vendor->getData());
        }

        return parent::_prepareForm();
    }

}
