<?php

namespace Unirgy\DropshipShippingClass\Block\Adminhtml\Vendor;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

class Edit extends Container
{
    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;

        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_DropshipShippingClass';
        $this->_controller = 'adminhtml_vendor';

        parent::__construct($context, $data);

        $this->buttonList->update('save', 'label', __('Save Vendor Ship Class'));
        $this->buttonList->update('delete', 'label', __('Delete Vendor Ship Class'));

        $this->setData('form_action_url', $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))));
    }

    public function getHeaderText()
    {
        if ($this->_registry->registry('udshipclass_vendor')->getId()) {
            return __("Edit Vendor Ship Class '%1'",
                      $this->escapeHtml($this->_registry->registry('udshipclass_vendor')->getClassName()));
        } else {
            return __('New Vendor Ship Class');
        }
    }

}
