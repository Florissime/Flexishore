<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class DeleteFolder extends AbstractImages
{
    public function execute()
    {
        try {
            $path = $this->getStorage()->getSession()->getCurrentPath();
            $this->getStorage()->deleteDirectory($path);
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));
        }
    }
}
