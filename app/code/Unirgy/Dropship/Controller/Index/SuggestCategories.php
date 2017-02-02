<?php

namespace Unirgy\Dropship\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Data as HelperData;

class SuggestCategories extends AbstractIndex
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Category list suggestion based on already entered symbols
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setJsonData(
            $this->layoutFactory->create()->createBlock('Magento\Catalog\Block\Adminhtml\Category\Tree')
                ->getSuggestedCategoriesJson($this->getRequest()->getParam('label_part'))
        );
    }
    /**
     * Initialize requested category and put it into registry.
     * Root category can be returned, if inappropriate store/category is specified
     *
     * @param bool $getRootInstead
     * @return \Magento\Catalog\Model\Category|false
     */
    protected function _initCategory($getRootInstead = false)
    {
        $categoryId = (int)$this->getRequest()->getParam('id', false);
        $storeId = (int)$this->getRequest()->getParam('store');
        $category = $this->_objectManager->create('Magento\Catalog\Model\Category');
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = $this->_objectManager->get(
                    'Magento\Store\Model\StoreManagerInterface'
                )->getStore(
                    $storeId
                )->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    // load root category instead wrong one
                    if ($getRootInstead) {
                        $category->load($rootId);
                    } else {
                        return false;
                    }
                }
            }
        }

        $this->_objectManager->get('Magento\Framework\Registry')->register('category', $category);
        $this->_objectManager->get('Magento\Framework\Registry')->register('current_category', $category);
        $this->_objectManager->get('Magento\Cms\Model\Wysiwyg\Config')
            ->setStoreId($this->getRequest()->getParam('store'));
        return $category;
    }

    /**
     * Check if admin has permissions to visit related pages
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
