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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit;

use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Block\Widget\Tabs as WidgetTabs;
use \Magento\Backend\Model\Auth\Session;
use \Magento\Framework\Json\EncoderInterface;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Tabs extends WidgetTabs
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var HelperData
     */
    protected $_hlp;


    public function __construct(
        Registry $frameworkRegistry,
        HelperData $helperData,
        Context $context,
        EncoderInterface $jsonEncoder, 
        Session $authSession, 
        array $data = []
    )
    {
        $this->_registry = $frameworkRegistry;
        $this->_hlp = $helperData;

        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->setId('statement_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Manage Statements'));
    }

    protected function _beforeToHtml()
    {
        $id = $this->getRequest()->getParam('id', 0);

        $statement = $this->_registry->registry('statement_data');
        $this->addTab('form_section', array(
            'label'     => __('Statement Information'),
            'title'     => __('Statement Information'),
            'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\Form')
                ->setVendorId($id)
                ->toHtml(),
        ));
        if ($this->_hlp->isUdpayoutActive()) {
            $this->addTab('payouts_section', array(
                'label'     => __('Payouts'),
                'title'     => __('Payouts'),
                'content'   => $this->getLayout()->createBlock('\Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\Payouts', 'statement.payouts.grid')->setVendorId($id)->toHtml(),
            ));
        }
        $this->addTab('rows_section', array(
            'label'     => __('Rows'),
            'title'     => __('Rows'),
            'content'   => $this->getLayout()->createBlock('\Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\Rows', 'statement.rows.grid')->setVendorId($id)->toHtml(),
        ));
        if ($this->_hlp->isStatementRefundsEnabled()) {
            $this->addTab('refund_rows_section', array(
                'label'     => __('Refunds'),
                'title'     => __('Refunds'),
                'content'   => $this->getLayout()->createBlock('\Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\RefundRows', 'statement.refund_rows.grid')->setVendorId($id)->toHtml(),
            ));
        }
        $this->addTab('adjustments_section', array(
            'label'     => __('Adjustments'),
            'title'     => __('Adjustments'),
            'content'   => $this->getLayout()->createBlock('\Unirgy\Dropship\Block\Adminhtml\Vendor\Statement\Edit\Tab\Adjustments', 'statement.adjustments.grid')->setVendorId($id)->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}
