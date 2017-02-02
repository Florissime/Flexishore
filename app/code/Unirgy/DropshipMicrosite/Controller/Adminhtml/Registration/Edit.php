<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Registration;

use Magento\Framework\Registry;
use Unirgy\DropshipMicrosite\Model\RegistrationFactory;

class Edit extends AbstractRegistration
{
    /**
     * @var RegistrationFactory
     */
    protected $_registrationFactory;

    public function __construct(
        RegistrationFactory $modelRegistrationFactory,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->_registrationFactory = $modelRegistrationFactory;

        parent::__construct($udropshipHelper, $micrositeHelper, $registry, $resultForwardFactory, $resultPageFactory, $resultRedirectFactory, $context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('reg_id');
        $reg = $this->_registrationFactory->create()->load($id);
        if (!$reg) {
            return $this->resultRedirectFactory->create()->setPath('umicrosite/registration/index');
        }
        $this->_registry->register('vendor_data', $reg->toVendor());

        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->setModule('udropship')->setController('vendor')->forward('edit');
        return $resultForward;
    }
}
