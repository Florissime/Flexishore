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

namespace Unirgy\Dropship\Block\Adminhtml\Shipping\Edit\Tab;

use \Magento\Backend\Block\Widget\Form;
use \Magento\Framework\Data\Form as DataForm;

class Methods extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    /**
     * @var \Unirgy\Dropship\Model\Source
     */
    protected $_src;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    public function __construct(
        \Magento\Shipping\Model\Config $shippingConfig,
        \Unirgy\Dropship\Model\Source $source,
        \Unirgy\Dropship\Helper\Data $helper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_src = $source;
        $this->_hlp = $helper;
        $this->_shippingConfig = $shippingConfig;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $shipping = $this->_coreRegistry->registry('shipping_data');

        if ($shipping) {
            $systemMethods = $shipping->getSystemMethods();
        }

        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('methods_fieldset', array('legend'=>__('Associated System Methods')));

        $carriers = $this->_shippingConfig->getAllCarriers();
        foreach ($carriers as $carrierCode=>$carrierModel) {
            if (in_array($carrierCode, array('udsplit', 'udropship','googlecheckout'))) {
                continue;
            }
            /*
            if (!$carrierModel->isActive() && (bool)$isActiveOnlyFlag === true) {
                continue;
            }
            */

            $params = array(
                'label'=>$this->_scopeConfig->getValue('carriers/'.$carrierCode.'/title'),
                'name'=>'system_methods['.$carrierCode.']',
                'type'=>'options',
                'value'=>isset($systemMethods[$carrierCode]) ? $systemMethods[$carrierCode] : '',
            );

            if ($carrierCode=='ups') {
                $params['values'] = array_merge_recursive(
                    array(array('value'=>'', 'label'=>__('* Not used'))),
                    array(array('value'=>'*', 'label'=>__('* Any available'))),
                    $this->_src->setPath('ups_shipping_method_combined')->toOptionArray()
                );
            } else {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (!$carrierMethods) {
                    $params['options'] = $this->_hlp->array_merge_n(
                        array(''=>__('* Not used')),
                        array('*'=>__('* Any available'))
                    );
                } else {
                    foreach ($carrierMethods as $cmCode=>$cmTitle) {
                        $carrierMethods[$cmCode] = (string)$cmTitle;
                    }
                    $array = [];
                    $arrays = [
                        array(''=>(string)__('* Not used')),
                        array('*'=>(string)__('* Any available')),
                        $carrierMethods
                    ];
                    foreach ($arrays as $array_i) {
                        if (is_array($array_i)) {
                            $this->_hlp->array_merge_2($array, $array_i);
                        }
                    }
                    $params['options'] = $array;
                }
            }

            $fieldset->addField('system_methods_'.$carrierCode, 'select', $params);
        }

        $this->setForm($form);
    }

    public function getTabLabel()
    {
        return __('Associated System Methods');
    }
    public function getTabTitle()
    {
        return __('Associated System Methods');
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
