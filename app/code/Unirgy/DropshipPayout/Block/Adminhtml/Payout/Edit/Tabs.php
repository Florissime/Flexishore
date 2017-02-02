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

namespace Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;

class Tabs extends WidgetTabs
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    public function __construct(
        Registry $frameworkRegistry,
        Context $context,
        EncoderInterface $jsonEncoder, 
        Session $authSession, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->setId('payout_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Manage Payouts'));
    }

    protected function _beforeToHtml()
    {
        $id = $this->getRequest()->getParam('id', 0);

        if ($id) {
            $payout = $this->_coreRegistry->registry('payout_data');
            $this->addTab('form_section', [
                'label'     => __('Payout Information'),
                'title'     => __('Payout Information'),
                'content'   => $this->getLayout()->createBlock('Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab\Form')
                    ->setVendorId($id)
                    ->toHtml(),
            ]);
            $this->addTab('rows_section', [
                'label'     => __('Data Rows'),
                'title'     => __('Data Rows'),
                'content'   => $this->getLayout()->createBlock('\Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab\Rows', 'udpayout.rows.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ]);
            $this->addTab('adjustments_section', [
                'label'     => __('Adjustments'),
                'title'     => __('Adjustments'),
                'content'   => $this->getLayout()->createBlock('\Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab\Adjustments', 'udpayout.adjustments.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ]);
        } else {
            $this->addTab('form_section', [
                'label'     => __('Payout Information'),
                'title'     => __('Payout Information'),
                'content'   => $this->getLayout()->createBlock('Unirgy\DropshipPayout\Block\Adminhtml\Payout\Edit\Tab\FormNew')
                    ->setVendorId($id)
                    ->toHtml(),
            ]);
        }

        return parent::_beforeToHtml();
    }
}
