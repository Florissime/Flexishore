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

namespace Unirgy\Dropship\Block\Adminhtml\Shipping;

use \Magento\Backend\Block\Widget\Context;
use \Magento\Backend\Block\Widget\Form\Container;
use \Magento\Framework\Registry;

class Edit extends Container
{
    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $dropshipHelper,
        Registry $registry,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $dropshipHelper;
        $this->_registry = $registry;

        parent::__construct($context, $data);

        $this->setData('form_action_url', $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))));

    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_Dropship';
        $this->_controller = 'adminhtml_shipping';

        parent::_construct();

        $this->updateButton('save', 'label', __('Save Shipping Method'));
        $this->updateButton('delete', 'label', __('Delete Shipping Method'));

        if( $this->getRequest()->getParam($this->_objectId) ) {
            $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Shipping')
                ->load($this->getRequest()->getParam($this->_objectId));
            $this->_registry->register('shipping_data', $model);
        }
    }

    public function getHeaderText()
    {
        if( $this->_registry->registry('shipping_data') && $this->_registry->registry('shipping_data')->getId() ) {
            $data = $this->_registry->registry('shipping_data');
            return __("Edit Method '%1'", $this->escapeHtml($data->getShippingCode()));
        } else {
            return __('New Method');
        }
    }
}
