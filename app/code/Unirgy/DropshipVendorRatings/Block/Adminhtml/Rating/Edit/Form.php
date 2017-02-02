<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\Form as DataForm;

class Form extends Generic
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', ['id' => $this->getRequest()->getParam('id')]),
                'method' => 'post'
            ]]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
