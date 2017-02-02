<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\Customer\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Form extends Generic
{

    public function _construct()
    {
        parent::_construct();
        $this->setId('udshipclassCustomerForm');
    }

    protected function _prepareForm()
    {
        $model  = $this->_coreRegistry->registry('udshipclass_customer');
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $this->setTitle(__('Customer Ship Class Information'));

        $fieldset   = $form->addFieldset('base_fieldset', [
            'legend'    => __('Customer Ship Class Information')
        ]);

        $fieldset->addField('class_name', 'text',
            [
                'name'  => 'class_name',
                'label' => __('Class Name'),
                'class' => 'required-entry',
                'value' => $model->getClassName(),
                'required' => true,
            ]
        );

        $fieldset->addField('sort_order', 'text', [
            'name'   => 'sort_order',
            'label'  => __('Sort Order'),
        ]);

        $fieldset->addType('shipclass_rows', 'Unirgy\DropshipShippingClass\Block\Adminhtml\FormField\ShipclassRows');

        $fieldset->addField('rows', 'shipclass_rows',
            [
                'name'  => 'rows',
                'label' => __('Countries'),
                'class' => 'required-entry',
                'value' => $model->getRows(),
                'required' => true,
            ]
        );

        if ($model->getId()) {
            $fieldset->addField('class_id', 'hidden',
                [
                    'name'      => 'class_id',
                    'value'     => $model->getId(),
                    'no_span'   => true
                ]
            );
        }

        $form->setValues($model->getData());
        $form->setAction($this->getUrl('*/customer/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
