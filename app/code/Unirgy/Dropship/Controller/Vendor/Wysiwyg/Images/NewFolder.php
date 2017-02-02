<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class NewFolder extends AbstractImages
{
    public function execute()
    {
        try {
            $this->_initAction();
            $name = $this->getRequest()->getPost('name');
            $path = $this->getStorage()->getSession()->getCurrentPath();
            $result = $this->getStorage()->createDirectory($name, $path);
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
        }
        return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));
    }
}
