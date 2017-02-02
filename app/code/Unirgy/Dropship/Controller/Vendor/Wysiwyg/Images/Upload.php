<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class Upload extends AbstractImages
{
    public function execute()
    {
        try {
            $result = [];
            $this->_initAction();
            $targetPath = $this->getStorage()->getSession()->getCurrentPath();
            $result = $this->getStorage()->uploadFile($targetPath, $this->getRequest()->getParam('type'));
            $result['tmp_name'] = str_replace(DIRECTORY_SEPARATOR, "/", $result['tmp_name']);
            $result['path'] = str_replace(DIRECTORY_SEPARATOR, "/", $result['path']);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        //usleep(10);
        return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));

    }
}
