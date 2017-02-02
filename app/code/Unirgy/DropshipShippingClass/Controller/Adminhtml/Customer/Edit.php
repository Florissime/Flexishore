<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Unirgy\DropshipShippingClass\Model\Customer;
use Unirgy\DropshipShippingClass\Model\CustomerFactory;

class Edit extends AbstractCustomer
{
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var Registry
     */
    protected $_registry;

    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        Registry $registry
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_registry = $registry;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage =  $this->_initAction();
        $title = $resultPage->getConfig()->getTitle();
        $title->prepend(__('Sales'));
        $title->prepend(__('Dropship'));
        $title->prepend(__('Customer Ship Classes'));

        $classId = $this->getRequest()->getParam('id');

        /** @var Customer $model */
        $model = $this->_customerFactory->create();
        if ($classId) {
            $model->load($classId);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This class no longer exists'));
                return $this->resultRedirectFactory->create()->setUrl('*/*/');
            }
        }

        $title->prepend($model->getId() ? __('"%1" Customer Ship Class',
                                           $model->getClassName()) : __('New Customer Ship Class'));

        $data = $this->_session->getUdshipclassCustomerData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_registry->register('udshipclass_customer', $model);

        $resultPage
            ->addBreadcrumb($classId ? __('Edit Class') : __('New Class'),
                             $classId ? __('Edit Customer Ship Class') : __('New Class'))
            ->addContent(
                $resultPage->getLayout()->createBlock('\Unirgy\DropshipShippingClass\Block\Adminhtml\Customer\Edit')
                    ->setData('action', $this->getUrl('*/customer/save'))
            );
        return $resultPage;
    }
}
