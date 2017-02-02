<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Unirgy\DropshipShippingClass\Model\CustomerFactory;

class Delete extends AbstractCustomer
{
    /**
     * @var CustomerFactory
     */
    protected $_modelCustomerFactory;

    public function __construct(
        Context $context,
        CustomerFactory $customerFactory
    ) {
        $this->_modelCustomerFactory = $customerFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $classId = $this->getRequest()->getParam('id');
        $classModel = $this->_modelCustomerFactory->create()
            ->load($classId);

        if (!$classModel->getId()) {
            $this->messageManager->addError(__('This class no longer exists'));
            return $this->_redirect('*/*/');
        }

        try {
            $classModel->delete();

            $this->messageManager->addSuccess(__('The ship class has been deleted.'));
            return $this->_redirect('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while deleting this ship class.'));
        }

        return $this->_redirect('*/*/');
    }
}
