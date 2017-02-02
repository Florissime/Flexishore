<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Review\Helper\Data as ReviewHelperData;
use Magento\Review\Model\ReviewFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use Unirgy\Dropship\Helper\Data as HelperData;

class Form extends Generic
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var ReviewHelperData
     */
    protected $_reviewHelper;

    /**
     * @var Store
     */
    protected $_systemStore;

    public function __construct(
        Context $context,
        Registry $registry, 
        FormFactory $formFactory, 
        HelperData $helperData, 
        CustomerFactory $modelCustomerFactory,
        ReviewFactory $modelReviewFactory, 
        ReviewHelperData $reviewHelperData,
        Store $systemStore,
        array $data = [])
    {
        $this->_hlp = $helperData;
        $this->_customerFactory = $modelCustomerFactory;
        $this->_reviewFactory = $modelReviewFactory;
        $this->_reviewHelper = $reviewHelperData;
        $this->_systemStore = $systemStore;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $review = $this->_coreRegistry->registry('review_data');
        $vendor = $this->_hlp->getVendor($review->getEntityPkValue());
        $shipment = $this->_hlp->createObj('Magento\Sales\Model\Order\Shipment')->load($review->getRelEntityPkValue());
        $customer = $this->_customerFactory->create()->load($review->getCustomerId());
        $statuses = $this->_reviewFactory->create()
            ->getStatusCollection()
            ->load()
            ->toOptionArray();

        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action'    => $this->getUrl('*/*/save', ['id' => $this->getRequest()->getParam('id'), 'ret' => $this->_coreRegistry->registry('ret')]),
                'method' => 'post'
            ]]
        );

        $fieldset = $form->addFieldset('review_details', ['legend' => __('Review Details'), 'class' => 'fieldset-wide']);

        $fieldset->addField('vendor_name', 'note', [
            'label'     => __('Vendor'),
            'text'      => '<a href="' . $this->getUrl('udropship/vendor/edit', ['id' => $vendor->getId()]) . '" onclick="this.target=\'blank\'">' . $vendor->getVendorName() . '</a>'
        ]);

        $fieldset->addField('shipment', 'note', [
            'label'     => __('Shipment'),
            'text'      => '<a href="' . $this->getUrl('sales/shipment/view', ['shipment_id' => $shipment->getId()]) . '" onclick="this.target=\'blank\'">' . $shipment->getIncrementId() . '</a>'
        ]);

        if ($customer->getId()) {
            $customerText = __('<a href="%1" onclick="this.target=\'blank\'">%2 %3</a> <a href="mailto:%4">(%4)</a>',
                $this->getUrl('customer/index/edit', ['id' => $customer->getId(), 'active_tab'=>'review']),
                $this->escapeHtml($customer->getFirstname()),
                $this->escapeHtml($customer->getLastname()),
                $this->escapeHtml($customer->getEmail()));
        } else {
            if (is_null($review->getCustomerId())) {
                $customerText = __('Guest');
            } elseif ($review->getCustomerId() == 0) {
                $customerText = __('Administrator');
            }
        }

        $fieldset->addField('customer', 'note', [
            'label'     => __('Posted By'),
            'text'      => $customerText,
        ]);

        $fieldset->addField('summary_rating', 'note', [
            'label'     => __('Summary Rating'),
            'text'      => $this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating\Summary')->toHtml(),
        ]);

        $fieldset->addField('detailed_rating', 'note', [
            'label'     => __('Detailed Rating'),
            'required'  => true,
            'text'      => '<div id="rating_detail">' . $this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating\Detailed')->toHtml() . '</div>',
        ]);

        $fieldset->addField('detailed_rating_na', 'note', [
            'label'     => __('Detailed Non-Aggregatable Rating'),
            'required'  => true,
            'text'      => '<div id="rating_detail_na">' . $this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Review\Rating\DetailedNa')->toHtml() . '</div>',
        ]);

        $fieldset->addField('status_id', 'select', [
            'label'     => __('Status'),
            'required'  => true,
            'name'      => 'status_id',
            'values'    => $statuses,
        ]);

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField('select_stores', 'multiselect', [
                'label'     => __('Visible In'),
                'required'  => true,
                'name'      => 'stores[]',
                'values'    => $this->_systemStore->getStoreValuesForForm()
            ]);
            $review->setSelectStores($review->getStores());
        }
        else {
            $fieldset->addField('select_stores', 'hidden', [
                'name'      => 'stores[]',
                'value'     => $this->_storeManager->getStore(true)->getId()
            ]);
            $review->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField('nickname', 'text', [
            'label'     => __('Nickname'),
            'required'  => true,
            'name'      => 'nickname'
        ]);

        $fieldset->addField('title', 'text', [
            'label'     => __('Summary of Review'),
            'required'  => true,
            'name'      => 'title',
        ]);

        $fieldset->addField('detail', 'textarea', [
            'label'     => __('Review'),
            'required'  => true,
            'name'      => 'detail',
            'style'     => 'height:24em;',
        ]);

        $form->setUseContainer(true);
        $form->setValues($review->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
