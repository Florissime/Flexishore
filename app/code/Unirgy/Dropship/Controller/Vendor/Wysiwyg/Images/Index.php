<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Wysiwyg\Images;
use Unirgy\Dropship\Model\Wysiwyg\Images\StorageFactory;

class Index extends AbstractImages
{
    public function execute()
    {
        $storeId = (int) $this->getRequest()->getParam('store');

        try {
            $this->_wysiwygImages->getCurrentPath();
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_view->addActionLayoutHandles();
        $block = $this->_view->getLayout()->getBlock('wysiwyg_images.js');
        if ($block) {
            $block->setStoreId($storeId);
        }
        return $this->_resultRawFactory->create()->setContents($block->toHtml());
    }
}
