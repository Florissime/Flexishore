<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Registration;

use Unirgy\DropshipMicrosite\Model\RegistrationFactory;

class ExportXml extends AbstractRegistration
{
    protected $_fileFactory;

    public function __construct(
        RegistrationFactory $modelRegistrationFactory,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    )
    {
        $this->_fileFactory = $fileFactory;

        parent::__construct($udropshipHelper, $micrositeHelper, $registry, $resultForwardFactory, $resultPageFactory, $resultRedirectFactory, $context);
    }

    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'registrations.xml';
        $content = $this->_view->getLayout()->getBlock('umicrosite.registration.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
