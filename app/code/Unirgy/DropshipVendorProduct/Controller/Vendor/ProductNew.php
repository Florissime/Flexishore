<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class ProductNew extends AbstractVendor
{
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        try {
            $result = parent::dispatch($request);
        } catch (\Exception $e) {
            if ($e->getMessage()==__('Product Limit Exceed')) {
                $this->messageManager->addError(__('Product Limit Exceed'));
                $this->_redirectAfterPost();
                return $this->_response;
            } else {
                throw $e;
            }
        }
        return $result;
    }
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $this->_request->setParam('id', null);
        try {
            /*
            if ($this->_hlp->isWysiwygAllowed()) {
                $this->_renderPage(['default', 'uwysiwyg_editor', 'uwysiwyg_editor_js'], 'udprod');
            } else {
                $this->_renderPage(null, 'udprod');
            }
            */
            $this->_renderPage(null, 'udprod');
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirectAfterPost();
        }
    }
}
