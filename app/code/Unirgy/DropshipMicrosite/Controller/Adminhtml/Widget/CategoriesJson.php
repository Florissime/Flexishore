<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;

class CategoriesJson extends AbstractWidget
{
    /**
     * @var RawFactory
     */
    protected $_resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $_viewLayoutFactory;

    public function __construct(Context $context, 
        CategoryFactory $modelCategoryFactory, 
        StoreManagerInterface $modelStoreManagerInterface, 
        Registry $frameworkRegistry, 
        RawFactory $resultRawFactory, 
        LayoutFactory $viewLayoutFactory)
    {
        $this->_resultRawFactory = $resultRawFactory;
        $this->_viewLayoutFactory = $viewLayoutFactory;

        parent::__construct($context, $modelCategoryFactory, $modelStoreManagerInterface, $frameworkRegistry);
    }

    public function execute()
    {
        if ($categoryId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $categoryId);

            if (!$category = $this->_initCategory()) {
                return;
            }
            $this->_resultRawFactory->create()->setContents(
                $this->_viewLayoutFactory->create()->createBlock('')
                    ->getTreeJson($category)
            );
        }
    }
}
