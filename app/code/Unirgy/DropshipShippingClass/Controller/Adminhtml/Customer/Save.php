<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception;
use Magento\Framework\Exception\LocalizedException;
use Unirgy\DropshipShippingClass\Model\Customer;
use Unirgy\DropshipShippingClass\Model\CustomerFactory;

class Save extends AbstractCustomer
{
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    public function __construct(
        Context $context,
        CustomerFactory $customerFactory
    ) {
        $this->_customerFactory = $customerFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        if ($postData = (array)$this->getRequest()->getPost()) {
            /** @var Customer $model */
            $model = $this->_customerFactory->create()
                ->setData($postData);

            try {
                $model->save();
                $classId = $model->getId();
                $classType = $model->getClassType();
                $classUrl = '*/udshipclassadmin_customer';

                $this->messageManager->addSuccess(__('The customer ship class has been saved.'));

                return $redirect->setUrl($classUrl);
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setUdshipclassCustomerData($postData);
                return $redirect->setRefererUrl();
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred while saving this customer ship class.'));
                $this->_session->setUdshipclassCustomerData($postData);
                return $redirect->setRefererUrl();
            }
        }
        return $redirect->setUrl($this->getUrl('*/customer'));
    }
}
