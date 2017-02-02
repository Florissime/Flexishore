<?php

namespace Unirgy\DropshipMicrosite\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractWidget extends Action
{
    /**
     * @var CategoryFactory
     */
    protected $_modelCategoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_modelStoreManagerInterface;

    /**
     * @var Registry
     */
    protected $_frameworkRegistry;

    public function __construct(Context $context, 
        CategoryFactory $modelCategoryFactory, 
        StoreManagerInterface $modelStoreManagerInterface, 
        Registry $frameworkRegistry)
    {
        $this->_modelCategoryFactory = $modelCategoryFactory;
        $this->_modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->_frameworkRegistry = $frameworkRegistry;

        parent::__construct($context);
    }


    protected function _initCategory()
    {
        $categoryId = (int) $this->getRequest()->getParam('id',false);

        $storeId    = (int) $this->getRequest()->getParam('store');

        $category = $this->_modelCategoryFactory->create();
        $category->setStoreId($storeId);

        if ($categoryId) {
            $category->load($categoryId);
            if ($storeId) {
                $rootId = $this->_modelStoreManagerInterface->getStore($storeId)->getRootCategoryId();
                if (!in_array($rootId, $category->getPathIds())) {
                    $this->_redirect('*/*/', ['_current'=>true, 'id'=>null]);
                    return false;
                }
            }
        }

        $this->_frameworkRegistry->register('category', $category);
        $this->_frameworkRegistry->register('current_category', $category);
        return $category;
    }
}