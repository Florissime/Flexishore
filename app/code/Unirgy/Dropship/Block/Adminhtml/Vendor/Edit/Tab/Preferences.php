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

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor;

class Preferences extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
     * @var \Unirgy\Dropship\Model\Config
     */
    protected $udropshipConfig;

    public function __construct(
        \Unirgy\Dropship\Model\Config $udropshipConfig,
        Registry $registry,
        HelperData $helper,
        Source $source,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->udropshipConfig = $udropshipConfig;
        $this->_hlp = $helper;
        $this->_src = $source;

        parent::__construct($context, $registry, $formFactory, $data);
        $this->setDestElementId('vendor_preferences');
        //$this->setTemplate('udropship/vendor/form.phtml');
    }

    protected function _prepareForm()
    {
        /** @var \Unirgy\Dropship\Model\Vendor $vendor */
        $vendor = $this->_coreRegistry->registry('vendor_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        if (!$vendor) {
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
        }
        $vendorData = $vendor->getData();

        $source = $this->_src;

        $fieldsets = array();
        foreach ($this->udropshipConfig->getFieldset() as $code=>$node) {
            if (@$node['modules'] && !$hlp->isModulesActive((string)$node['modules'])
                || @$node['hide_modules'] && $hlp->isModulesActive((string)@$node['hide_modules'])
                || @$node['hidden']
            ) {
                continue;
            }
            $fieldsets[$code] = array(
                'position' => (int)$node['position'],
                'params' => array(
                    'legend' => (string)$node['legend']
                ),
            );
        }
        foreach ($this->udropshipConfig->getField() as $code=>$node) {
            if (empty($fieldsets[(string)@$node['fieldset']]) || @$node['disabled']) {
                continue;
            }
            if (@$node['modules'] && !$hlp->isModulesActive((string)$node['modules'])
                || @$node['hide_modules'] && $hlp->isModulesActive((string)@$node['hide_modules'])
            ) {
                continue;
            }
            $type = @$node['type'] ? (string)@$node['type'] : 'text';
            $field = array(
                'position' => (int)@$node['position'],
                'type' => $type,
                'params' => array(
                    'name' => @$node['name'] ? (string)@$node['name'] : $code,
                    'label' => (string)@$node['label'],
                    'class' => (string)@$node['class'],
                    'note' => (string)@$node['note'],
                    'field_config' => $node
                ),
            );
            if (@$node['name'] && (string)$node['name'] != $code && !isset($vendorData[$code])) {
                $vendorData[$code] = @$vendorData[(string)$node['name']];
            }
            if (@$node['frontend_model']) {
                $field['type'] = $code;
                $this->addAdditionalElementType($code, $node['frontend_model']);
            }
            switch ($type) {
            case 'statement_po_type': case 'payout_po_status_type': case 'notify_lowstock':
            case 'select': case 'multiselect': case 'checkboxes': case 'radios':
                $source = $this->_hlp->getObj(@$node['source_model'] ? (string)$node['source_model'] : '\Unirgy\Dropship\Model\Source');
                if (is_callable(array($source, 'setPath'))) {
                    $source->setPath(@$node['source'] ? (string)$node['source'] : $code);
                }
                if (in_array($type, array('multiselect', 'checkboxes', 'radios')) || !is_callable(array($source, 'toOptionHash'))) {
                    $field['params']['values'] = $source->toOptionArray();
                } else {
                    $field['params']['options'] = $source->toOptionHash();
                }
                break;

            case 'date': case 'datetime':
                $field['params']['input_format'] = \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT;
                $field['params']['format'] = \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT;
                break;
            }
            $fieldsets[(string)$node['fieldset']]['fields'][$code] = $field;
        }

        uasort($fieldsets, array($hlp, 'usortByPosition'));
        foreach ($fieldsets as $k=>$v) {
            if (empty($v['fields'])) {
                continue;
            }
            $fieldset = $form->addFieldset($k, $v['params']);
            $this->_addElementTypes($fieldset);
            uasort($v['fields'], array($hlp, 'usortByPosition'));
            foreach ($v['fields'] as $k1=>$v1) {
                $fieldset->addField($k1, $v1['type'], $v1['params']);
            }
        }

        $form->setValues($vendorData);

        return parent::_prepareForm();
    }

    protected $_additionalElementTypes = null;
    protected function _initAdditionalElementTypes()
    {
        if (is_null($this->_additionalElementTypes)) {
        $this->_additionalElementTypes = array(
            'wysiwyg' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\Wysiwyg',
            'statement_po_type' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\StatementPoType',
            'payout_po_status_type' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\PayoutPoStatusType',
            'notify_lowstock' => '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\NotifyLowstock',
        );
        }
        return $this;
    }
    protected function _getAdditionalElementTypes()
    {
        $this->_initAdditionalElementTypes();
        return $this->_additionalElementTypes;
    }
    public function addAdditionalElementType($code, $class)
    {
        $this->_initAdditionalElementTypes();
        $this->_additionalElementTypes[$code] = $class;
        return $this;
    }

    public function getTabLabel()
    {
        return __('Preferences');
    }
    public function getTabTitle()
    {
        return __('Preferences');
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
