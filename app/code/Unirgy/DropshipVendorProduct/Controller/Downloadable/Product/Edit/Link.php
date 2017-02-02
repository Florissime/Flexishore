<?php

namespace Unirgy\DropshipVendorProduct\Controller\Downloadable\Product\Edit;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Downloadable\Helper\Download;
use Magento\Downloadable\Helper\File;
use Magento\Downloadable\Model\Link as ModelLink;
use Magento\Downloadable\Model\LinkFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Data as HelperData;

class Link extends AbstractEdit
{
    /**
     * @var LinkFactory
     */
    protected $_modelLinkFactory;

    /**
     * @var File
     */
    protected $_helperFile;

    public function __construct(Context $context, 
        ScopeConfigInterface $scopeConfig, 
        DesignInterface $viewDesignInterface, 
        StoreManagerInterface $storeManager, 
        LayoutFactory $viewLayoutFactory, 
        Registry $registry, 
        ForwardFactory $resultForwardFactory, 
        HelperData $helper, 
        PageFactory $resultPageFactory, 
        RawFactory $resultRawFactory, 
        Header $httpHeader, 
        Download $helperDownload, 
        LinkFactory $modelLinkFactory, 
        File $helperFile)
    {
        $this->_modelLinkFactory = $modelLinkFactory;
        $this->_helperFile = $helperFile;

        parent::__construct($context, $scopeConfig, $viewDesignInterface, $storeManager, $viewLayoutFactory, $registry, $resultForwardFactory, $helper, $resultPageFactory, $resultRawFactory, $httpHeader, $helperDownload);
    }

    /**
     * Download link action
     *
     */
    public function execute()
    {
        $linkId = $this->getRequest()->getParam('id', 0);
        $link = $this->_modelLinkFactory->create()->load($linkId);
        if ($link->getId()) {
            $resource = '';
            $resourceType = '';
            if ($link->getLinkType() == Download::LINK_TYPE_URL) {
                $resource = $link->getLinkUrl();
                $resourceType = Download::LINK_TYPE_URL;
            } elseif ($link->getLinkType() == Download::LINK_TYPE_FILE) {
                $resource = $this->_helperFile->getFilePath(
                    ModelLink::getBasePath(), $link->getLinkFile()
                );
                $resourceType = Download::LINK_TYPE_FILE;
            }
            try {
                $this->_processDownload($resource, $resourceType);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred while getting the requested content.'));
            }
        }
        exit(0);
    }
}
