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

use \Magento\Backend\Block\Widget\Form as WidgetForm;
use \Magento\Config\Model\Config\Source\Website;
use \Magento\Framework\Data\Form as DataForm;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Website
     */
    protected $_sourceWebsite;

    public function __construct(
        HelperData $helperData,
        Website $sourceWebsite,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_sourceWebsite = $sourceWebsite;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $cert = $this->_coreRegistry->registry('shipping_data');
        $hlp = $this->_hlp;
        $id = $this->getRequest()->getParam('id');
        $form = $this->_formFactory->create();
        $this->setForm($form);

        $fieldset = $form->addFieldset('shipping_form', array(
            'legend'=>__('Shipping Info')
        ));

        $fieldset->addField('shipping_code', 'text', array(
            'name'      => 'shipping_code',
            'label'     => __('Shipping Method Code'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('shipping_title', 'text', array(
            'name'      => 'shipping_title',
            'label'     => __('Shipping Method Title'),
            'class'     => 'required-entry',
            'required'  => true,
        ));


        $fieldset->addField('days_in_transit', 'text', array(
            'name'      => 'days_in_transit',
            'label'     => __('Days In Transit'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $options = $this->_sourceWebsite->toOptionArray();
        array_unshift($options, array('label'=>'All websites', 'value'=>0));
        $fieldset->addField('website_ids', 'multiselect', array(
            'name'      => 'website_ids[]',
            'label'     => __('Websites'),
            'title'     => __('Websites'),
            'required'  => true,
            'values'    => $options,
        ));

        $this->_eventManager->dispatch('udropship_adminhtml_shipping_edit_prepare_form', array('block'=>$this, 'form'=>$form, 'id'=>$id));

        if ($this->_coreRegistry->registry('shipping_data')) {
            $form->setValues($this->_coreRegistry->registry('shipping_data')->getData());
        }

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Shipping Information');
    }
    public function getTabTitle()
    {
        return __('Shipping Information');
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