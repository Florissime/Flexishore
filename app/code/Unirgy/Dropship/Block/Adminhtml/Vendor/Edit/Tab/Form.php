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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab;

use \Magento\Backend\Block\Widget\Form as WidgetForm;
use \Magento\Config\Model\Config\Source\Email\Template;
use \Magento\Directory\Model\Config\Source\Country;
use \Magento\Framework\Data\Form as DataForm;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;

class Form extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Template
     */
    protected $_templatesFactory;

    /**
     * @var Country
     */
    protected $_sourceCountry;

    public function __construct(
        Registry $registry,
        HelperData $helperData,
        Country $sourceCountry,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templatesFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_templatesFactory = $templatesFactory;
        $this->_sourceCountry = $sourceCountry;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('vendor_form');
        //$this->setTemplate('udropship/vendor/form.phtml');
    }

    protected function _prepareForm()
    {
        $vendor = $this->_coreRegistry->registry('vendor_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('vendor_form', array(
            'legend'=>__('Vendor Info')
        ));

        $fieldset->addField('reg_id', 'hidden', array(
            'name'      => 'reg_id',
        ));
        $fieldset->addField('password_hash', 'hidden', array(
            'name'      => 'password_hash',
        ));
        $fieldset->addField('save_continue', 'hidden', array(
            'name'      => 'save_continue',
        ));

        $fieldset->addField('vendor_name', 'text', array(
            'name'      => 'vendor_name',
            'label'     => __('Vendor Name'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        if ($this->_hlp->isModuleActive('Unirgy_DropshipMicrositePro')) {
            $fieldset->addType('udvendor_status', '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\DependSelect');
            $udVendorStatusType = 'udvendor_status';
            $udVendorStatusFC = array(
                'depend_fields' => array(
                    'reject_reason' => 'R',
                    'send_reject_email' => 'R',
                    'send_confirmation_email' => 'A'
                )
            );
        } else {
            $udVendorStatusFC = array();
            $udVendorStatusType = 'select';
        }

        $fieldset->addField('status', $udVendorStatusType, array(
            'name'      => 'status1',
            'label'     => __('Status'),
            'class'     => 'required-entry',
            'required'  => true,
            'options'   => $this->_hlp->src()->setPath('vendor_statuses')->toOptionHash(),
            'field_config' => $udVendorStatusFC
        ));
        if ($this->_hlp->isModuleActive('Unirgy_DropshipVendorMembership')) {
            /*
            $profile = $this->_recurringProfile;
            if ($vendor && $vendor->getData('udmember_profile_id')) {
                $profile->load($vendor->getData('udmember_profile_id'));
            }
            $fieldset->addField('__udmember_profile', 'note', array(
                'name'      => '__udmember_profile',
                'label'     => __('Membership Profile'),
                'text'      => $profile->getId() ? sprintf('<a href="%s">%s (%s)</a>', $this->getUrl('adminhtml/sales_recurring_profile/view', array('profile'=>$profile->getId())), $profile->getReferenceId(), $profile->renderData('state')) : '',
            ));
            */
            $mOptions = $this->_hlp->createObj('\Unirgy\DropshipVendorMembership\Model\Membership')->getCollection()->toOptionHash('membership_code', 'membership_title');
            if ($vendor && ($mCode = $vendor->getData('udmember_membership_code'))) {
                $mTitle = $vendor->getData('udmember_membership_title');
                $mOptions[$mCode] = $mTitle ? $mTitle : $mCode;
            }
            $mOptions = array(''=>__('* Please select')) + $mOptions;
            $fieldset->addField('udmember_membership_code', 'select', array(
                'name'      => 'udmember_membership_code',
                'label'     => __('Membership'),
                'options'   => $mOptions,
            ));
            $fieldset->addField('udmember_allow_microsite', 'select', array(
                'name'      => 'udmember_allow_microsite',
                'label'     => __('Allow Microsite'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            ));
            $fieldset->addField('udmember_limit_products', 'text', array(
                'name'      => 'udmember_limit_products',
                'label'     => __('Limit Products'),
            ));
            if ($vendor && $vendor->getData('udmember_billing_type')) {
                $fieldset->addField('__billing_type', 'note', array(
                    'name'      => '__billing_type',
                    'label'     => __('Billing Type'),
                    'text'      => $vendor->getData('udmember_billing_type'),
                ));
            }
            $fieldset->addField('udmember_membership_title', 'hidden', array(
                'name' => 'udmember_membership_title',
            ));
            /*
            $fieldset->addField('udmember_profile_sync_off', 'select', array(
                'name' => 'udmember_profile_sync_off',
                'label'     => __('DO NOT Automatically synchronize status with profile'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            ));
            */
            $fieldset->addField('udmember_billing_type', 'hidden', array(
                'name' => 'udmember_billing_type',
            ));
        }

        if ($this->_hlp->isModuleActive('Unirgy_DropshipMicrositePro')) {
            $fieldset->addField('reject_reason', 'textarea', array(
                'name'      => 'reject_reason',
                'label'     => __('Reject Reason'),
                'class'     => 'required-entry',
                'required'  => true,
                'style'     => 'height:100px',
            ));
            $fieldset->addField('send_reject_email', 'select', array(
                'name'      => 'send_reject_email',
                'label'     => __('Send Reject Email'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            ));
            $fieldset->addField('send_confirmation_email', 'select', array(
                'name'      => 'send_confirmation_email',
                'label'     => $vendor && $vendor->getConfirmationSent()
                    ? __('Resend Confirmation Email')
                    : __('Send Confirmation Email'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
                'note'      => $vendor && $vendor->getConfirmationSent()
                    ? __('Resending confirmation email will reset password (revoke old one). New password will be sent to vendor in separate email once he click at the link in this confirmation email.')
                    : __('Send Confirmation Email. Password will be sent to vendor in separate email once he click at the link in this confirmation email.'),
            ));
            $fieldset->addField('confirmation', 'select', array(
                'name'      => 'confirmation',
                'label'     => __('Waiting for email confirmation'),
                'options'   => array(
                    '' => __('No'),
                    ($vendor && $vendor->getConfirmation() ? $vendor->getConfirmation() : 1) => __('Yes'),
                ),
            ));
        }

        $fieldset->addField('carrier_code', 'select', array(
            'name'      => 'carrier_code',
            'label'     => __('Preferred Carrier'),
            'class'     => 'required-entry',
            'required'  => true,
            'options'   => $this->_hlp->src()->setPath('carriers')->toOptionHash(true),
        ));

        $fieldset->addField('use_rates_fallback', 'select', array(
            'name'      => 'use_rates_fallback',
            'label'     => __('Use Rates Fallback Chain'),
            'class'     => 'required-entry',
            'required'  => true,
            'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            'note'      => __('Will try to find available estimate rate for dropship shipping methods in order <br>1. Estimate Carrier <br>2. Override Carrier <br>3. Default Carrier'),
        ));

        $fieldset->addField('email', 'text', array(
            'name'      => 'email',
            'label'     => __('Vendor Email'),
            'class'     => 'required-entry validate-email',
            'required'  => true,
            'note'      => __('Email is also used as username'),
        ));

        $fieldset->addField('password', 'password', array(
            'name'      => 'password',
            'label'     => __('New Password'),
            'class'     => 'validate-password',
            'note'      => __('Leave empty for no change'),
        ));
/*
        $fieldset->addField('password', 'password', array(
            'name'      => 'password',
            'label'     => __('Log In Password'),
            'note'      => __('Login disabled if empty'),
        ));
*/
        $fieldset->addField('telephone', 'text', array(
            'name'      => 'telephone',
            'label'     => __('Vendor Telephone'),
            'note'      => __('Phone number is required for FedEx label printing'),
        ));

        $fieldset->addField('fax', 'text', array(
            'name'      => 'fax',
            'label'     => __('Vendor Fax'),
        ));

        $templates = $this->_templatesFactory->create()->load()->toOptionArray();
        array_unshift($templates, ['value' => '', 'label' => __('Use Default Configuration')]);
        $fieldset->addField('email_template', 'select', array(
            'name'      => 'email_template',
            'label'     => __('Notification Template'),
            'values'   => $templates,
        ));

        $fieldset->addField('vendor_shipping', 'hidden', array(
            'name' => 'vendor_shipping',
        ));
        $fieldset->addField('vendor_products', 'hidden', array(
            'name' => 'vendor_products',
        ));

        if ($this->_scopeConfig->isSetFlag('udropship/customer/allow_shipping_extra_charge')) {
            $fieldset->addField('allow_shipping_extra_charge', 'select', array(
                'name'      => 'allow_shipping_extra_charge',
                'label'     => __('Allow shipping extra charge'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            ));
            $fieldset->addField('default_shipping_extra_charge_suffix', 'text', array(
                'name'      => 'default_shipping_extra_charge_suffix',
                'label'     => __('Default shipping extra charge suffix'),
            ));
            $fieldset->addField('default_shipping_extra_charge_type', 'select', array(
                'name'      => 'default_shipping_extra_charge_type',
                'label'     => __('Default shipping extra charge type'),
                'options'   => $this->_hlp->src()->setPath('shipping_extra_charge_type')->toOptionHash(true),
            ));
            $fieldset->addField('default_shipping_extra_charge', 'text', array(
                'name'      => 'default_shipping_extra_charge',
                'label'     => __('Default shipping extra charge'),
            ));
            $fieldset->addField('is_extra_charge_shipping_default', 'select', array(
                'name'      => 'is_extra_charge_shipping_default',
                'label'     => __('Is extra charge shipping default'),
                'options'   => $this->_hlp->src()->setPath('yesno')->toOptionHash(true),
            ));
        }

/*
        $fieldset->addField('url_key', 'text', array(
            'name'      => 'url_key',
            'label'     => __('URL friendly identifier'),
        ));
*/
        $countries = $this->_sourceCountry
            ->toOptionArray();
        //unset($countries[0]);


        $countryId = $this->_coreRegistry->registry('vendor_data') ? $this->_coreRegistry->registry('vendor_data')->getCountryId() : null;
        if (!$countryId) {
            $countryId = $this->_scopeConfig->getValue('general/country/default');
        }

        $regionCollection = $this->_hlp->createObj('\Magento\Directory\Model\Region')
            ->getCollection()
            ->addCountryFilter($countryId);

        $regions = $regionCollection->toOptionArray();

        if ($regions) {
            $regions[0]['label'] = __('Please select state...');
        } else {
            $regions = array(array('value'=>'', 'label'=>''));
        }

        $fieldset = $form->addFieldset('address_form', array(
            'legend'=>__('Shipping Origin Address')
        ));

        $fieldset->addField('vendor_attn', 'text', array(
            'name'      => 'vendor_attn',
            'label'     => __('Attention To'),
        ));

        $fieldset->addField('street', 'textarea', array(
            'name'      => 'street',
            'label'     => __('Street'),
            'class'     => 'required-entry',
            'required'  => true,
            'style'     => 'height:50px',
        ));

        $fieldset->addField('city', 'text', array(
            'name'      => 'city',
            'label'     => __('City'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('zip', 'text', array(
            'name'      => 'zip',
            'label'     => __('Zip / Postal code'),
        ));

        $country = $fieldset->addField('country_id', 'select',
            array(
                'name' => 'country_id',
                'label' => __('Country'),
                'title' => __('Please select Country'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $countries,
            )
        );

        $fieldset->addField('region_id', 'select',
            array(
                'name' => 'region_id',
                'label' => __('State'),
                'title' => __('Please select State'),
                'values' => $regions,
            )
        );
        $fieldset->addField('region', 'text',
            array(
                'name' => 'region',
                'label' => __('State'),
                'title' => __('Please select State'),
            )
        );

        $bCountryId = $this->_coreRegistry->registry('vendor_data') ? $this->_coreRegistry->registry('vendor_data')->getBillingCountryId() : null;
        if (!$bCountryId) {
            $bCountryId = $this->_scopeConfig->getValue('general/country/default');
        }

        $fieldset = $form->addFieldset('billing_form', array(
            'legend'=>__('Billing Address')
        ));

        $fieldset->addType('billing_use_shipping', '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\DependSelect');

        $fieldset->addField('billing_use_shipping', 'billing_use_shipping', array(
            'name'      => 'billing_use_shipping',
            'label'     => __('Same as Shipping'),
            'options'   => $this->_hlp->src()->setPath('billing_use_shipping')->toOptionHash(),
            'field_config' => array(
                'depend_fields' => array(
                    'billing_vendor_attn' => '0',
                    'billing_street' => '0',
                    'billing_city' => '0',
                    'billing_zip' => '0',
                    'billing_country_id' => '0',
                    'billing_region_id' => '0',
                    'billing_region' => '0',
                    'billing_email' => '0',
                    'billing_telephone' => '0',
                    'billing_fax' => '0',
                )
            )
        ));

        $fieldset->addField('billing_vendor_attn', 'text', array(
            'name'      => 'billing_vendor_attn',
            'label'     => __('Attention To'),
            'note'      => __('Leave empty to use shipping origin'),
        ));

        $fieldset->addField('billing_street', 'textarea', array(
            'name'      => 'billing_street',
            'label'     => __('Street'),
            'class'     => 'required-entry',
            'required'  => true,
            'style'     => 'height:50px',
        ));

        $fieldset->addField('billing_city', 'text', array(
            'name'      => 'billing_city',
            'label'     => __('City'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('billing_zip', 'text', array(
            'name'      => 'billing_zip',
            'label'     => __('Zip / Postal code'),
        ));

        $bCountry = $fieldset->addField('billing_country_id', 'select',
            array(
                'name' => 'billing_country_id',
                'label' => __('Country'),
                'title' => __('Please select Country'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $countries,
            )
        );

        $fieldset->addField('billing_region_id', 'select',
            array(
                'name' => 'billing_region_id',
                'label' => __('State'),
                'title' => __('Please select State'),
                'values' => $regions,
            )
        );
        $fieldset->addField('billing_region', 'text',
            array(
                'name' => 'billing_region',
                'label' => __('State'),
                'title' => __('Please select State'),
            )
        );

        $fieldset->addField('billing_email', 'text', array(
            'name'      => 'billing_email',
            'label'     => __('Email'),
            'class'     => 'validate-email',
            'note'      => __('Leave empty to use default'),
        ));

        $fieldset->addField('billing_telephone', 'text', array(
            'name'      => 'billing_telephone',
            'label'     => __('Telephone'),
            'note'      => __('Leave empty to use default'),
        ));

        $fieldset->addField('billing_fax', 'text', array(
            'name'      => 'billing_fax',
            'label'     => __('Fax'),
            'note'      => __('Leave empty to use default'),
        ));

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_edit_prepare_form', array('block'=>$this, 'form'=>$form, 'id'=>$id));

        if ($vendor) {
            if ($this->getRequest()->getParam('reg_id')) {
                $shipping = array();
                foreach ($vendor->getShippingMethods() as $sId=>$_s) {
                    foreach ($_s as $s) {
                        $shipping[$sId][] = array(
                            'on' => 1,
                            'est_carrier_code' => $s['est_carrier_code'],
                            'carrier_code' => $s['carrier_code'],
                        );
                    }
                }
                $vendor->setVendorShipping(\Zend_Json::encode($shipping));
                $vendor->setSendConfirmationEmail(!$this->_scopeConfig->isSetFlag('udropship/microsite/skip_confirmation'));
            } else {
                try {
                    \Zend_Json::decode($vendor->getVendorShipping());
                } catch (\Exception $e) {
                    $vendor->setVendorShipping('{}');
                }
            }
            $form->setValues($vendor->getData());
        }

        if (!$id) {
            $country->setValue($countryId);
            $bCountry->setValue($bCountryId);
        }

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Vendor Information');
    }
    public function getTabTitle()
    {
        return __('Vendor Information');
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
