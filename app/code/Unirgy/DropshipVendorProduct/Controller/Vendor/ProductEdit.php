<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class ProductEdit extends AbstractVendor
{

    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $oldStoreId = $this->_storeManager->getStore()->getId();
        try {
            $this->_storeManager->setCurrentStore(0);
            $this->_checkProduct();
            $this->_storeManager->setCurrentStore($oldStoreId);
            /*
            if ($this->_hlp->isWysiwygAllowed()) {
                $this->_renderPage(['default', 'uwysiwyg_editor', 'uwysiwyg_editor_js'], 'udprod');
            } else {
                $this->_renderPage(null, 'udprod');
            }
            */
            $this->_renderPage(null, 'udprod');
        } catch (\Exception $e) {
            $this->_storeManager->setCurrentStore($oldStoreId);
            $this->messageManager->addError($e->getMessage());
            $this->_redirectAfterPost();
        }
    }
}
