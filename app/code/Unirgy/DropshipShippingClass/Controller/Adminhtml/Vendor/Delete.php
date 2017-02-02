<?php

namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception;
use Magento\Framework\Exception\LocalizedException;
use Unirgy\DropshipShippingClass\Model\Vendor;
use Unirgy\DropshipShippingClass\Model\VendorFactory;

class Delete extends AbstractVendor
{
    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    public function __construct(
        Context $context,
        VendorFactory $vendorFactory
    ) {
        $this->_vendorFactory = $vendorFactory;

        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $classId = $this->getRequest()->getParam('id');
        /** @var Vendor $model */
        $model = $this->_vendorFactory->create()
            ->load($classId);

        if (!$model->getId()) {
            $this->messageManager->addError(__('This class no longer exists'));
            return $this->_redirect('*/*/');
        }

        try {
            $model->delete();

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
