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
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Block\Adminhtml\Payout;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\DropshipPayout\Model\PayoutFactory;

class Edit extends Container
{
    /**
     * @var PayoutFactory
     */
    protected $_payoutFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    public function __construct(Context $context, 
        PayoutFactory $modelPayoutFactory, 
        Registry $frameworkRegistry, 
        array $data = [])
    {
        $this->_payoutFactory = $modelPayoutFactory;
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Unirgy_DropshipPayout';
        $this->_controller = 'adminhtml_payout';

        parent::_construct();

        if ($this->getRequest()->getParam($this->_objectId)) {
            $this->updateButton('delete', 'label', __('Delete Payout'));
            $model = $this->_payoutFactory->create()
                ->load($this->getRequest()->getParam($this->_objectId));
            $this->_coreRegistry->register('payout_data', $model);
            if ($model->getPayoutStatus() != Payout::STATUS_PAID
                && $model->getPayoutStatus() != Payout::STATUS_PAYPAL_IPN
                && $model->getPayoutStatus() != Payout::STATUS_CANCELED
                && $model->getPayoutStatus() != Payout::STATUS_HOLD
            ) {
                $this->addButton('save_pay', [
                    'label'     => __('Save and Pay'),
                    'onclick'   => "\$('pay_flag').value=1; $('edit_form').submit();",
                    'class'     => 'save',
                ], 1);
            }
        } else {
            $this->updateButton('save', 'label', __('Create Payout(s)'));
        }
    }

    public function getHeaderText()
    {
        return __('Payout');
    }
}
