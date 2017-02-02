<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action;

abstract class AbstractStatement extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_hlp = $udropshipHelper;
        $this->_registry = $registry;
        $this->_fileFactory = $fileFactory;
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_resultRedirectFactory = $resultRedirectFactory;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Unirgy_Dropship::statement');
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Unirgy_Dropship::statement');
        $resultPage->getConfig()->getTitle()->prepend(__('Vendor Statements'));
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Dropship'), __('Dropship'));
        $resultPage->addBreadcrumb(__('Vendor Statements'), __('Vendor Statements'));
        return $resultPage;

    }

}
