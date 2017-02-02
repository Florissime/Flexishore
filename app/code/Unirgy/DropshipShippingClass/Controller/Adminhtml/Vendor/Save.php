<?php
namespace Unirgy\DropshipShippingClass\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception;
use Magento\Framework\Exception\LocalizedException;
use Unirgy\DropshipShippingClass\Model\Vendor;
use Unirgy\DropshipShippingClass\Model\VendorFactory;

class Save extends AbstractVendor
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

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        if ($postData = (array)$this->getRequest()->getPost()) {
            unset($postData['rows']['$ROW']);
            /** @var Vendor $model */
            $model = $this->_vendorFactory->create()
                ->setData($postData);

            try {
                $model->save();
                $classId = $model->getId();
                $classType = $model->getClassType();
                $classUrl = '*/vendor';

                $this->messageManager->addSuccess(__('The vendor ship class has been saved.'));
                return $redirect->setUrl($classUrl);
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_session->setUdshipclassVendorData($postData);
                return $redirect->setRefererUrl();
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred while saving this vendor ship class.'));
                $this->_session->setUdshipclassVendorData($postData);
                return $redirect->setRefererUrl();
            }
        }
        return $redirect->setUrl($this->getUrl('*/vendor'));
    }
}
