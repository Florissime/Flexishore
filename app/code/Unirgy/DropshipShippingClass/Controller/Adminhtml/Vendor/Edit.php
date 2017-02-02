<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Unirgy\DropshipShippingClass\Model\Vendor;
use Unirgy\DropshipShippingClass\Model\VendorFactory;

class Edit extends AbstractVendor
{
    /**
     * @var VendorFactory
     */
    protected $vendorFactory;

    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        Context $context,
        VendorFactory $vendorFactory,
        Registry $registry
    ) {
        $this->vendorFactory = $vendorFactory;
        $this->_registry = $registry;

        parent::__construct($context);
    }

    public function execute()
    {

        $resultPage = $this->_initAction();
        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Vendor Ship Classes'));

        $classId = $this->getRequest()->getParam('id');

        /** @var Vendor $model */
        $model = $this->vendorFactory->create();
        if ($classId) {
            $model->load($classId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This class no longer exists'));
                return $this->resultRedirectFactory->create()->setUrl('*/*/');
            }
        }

        $title->prepend($model->getId() ? __('"%1" Vendor Ship Class',
                                             $model->getClassName()) : __('New Vendor Ship Class'));

        $data = $this->_session->getUdshipclassVendorData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_registry->register('udshipclass_vendor', $model);

        return $resultPage
            ->addBreadcrumb($classId ? __('Edit Class') : __('New Class'),
                             $classId ? __('Edit Vendor Ship Class') : __('New Class'))
            ->addContent($this->_view->getLayout()->createBlock('\Unirgy\DropshipShippingClass\Block\Adminhtml\Vendor\Edit')
                             ->setData('action', $this->getUrl('*/vendor/save')));
    }
}
