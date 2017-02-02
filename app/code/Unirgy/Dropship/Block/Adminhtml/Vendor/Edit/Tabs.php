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

namespace Unirgy\Dropship\Block\Adminhtml\Vendor\Edit;

use \Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Manage Vendors'));
    }

    protected function _beforeToHtml()
    {
        $id = $this->getRequest()->getParam('id', 0);

        $this->addTab('form_section', array(
            'label'     => __('Vendor Information'),
            'title'     => __('Vendor Information'),
            'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Form')
                ->setVendorId($id)
                ->toHtml(),
        ));

        $this->addTab('preferences_section', array(
            'label'     => __('Preferences'),
            'title'     => __('Preferences'),
            'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Preferences', 'vendor.preferences.form')
                ->setVendorId($id)
                ->toHtml(),
        ));

        $this->addTab('custom_section', array(
            'label'     => __('Custom Data'),
            'title'     => __('Custom Data'),
            'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Custom', 'vendor.custom.form')
                ->setVendorId($id)
                ->toHtml(),
        ));

        $this->addTab('shipping_section', array(
            'label'     => __('Shipping methods'),
            'title'     => __('Shipping methods'),
            'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Shipping', 'vendor.shipping.grid')
                ->setVendorId($id)
                ->toHtml(),
        ));

        if ($id) {
            $this->addTab('products_section', array(
                'label'     => __('Associated Products'),
                'title'     => __('Associated Products'),
                'content'   => $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\Vendor\Edit\Tab\Products', 'vendor.product.grid')
                    ->setVendorId($id)
                    ->toHtml(),
            ));
        }

        if(($tabId = $this->getRequest()->getParam('tab'))) {
            $this->setActiveTab($tabId);
        }

        $this->_eventManager->dispatch('udropship_adminhtml_vendor_tabs_after', array('block'=>$this, 'id'=>$id));

        return parent::_beforeToHtml();
    }
}