<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class DeleteFiles extends AbstractImages
{
    public function execute()
    {
        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception ('Wrong request.');
            }
            $files = $this->_hlp->jsonDecode($this->getRequest()->getParam('files'));

            /** @var $helper \Magento\Cms\Helper\Wysiwyg\Images */
            $helper = $this->_wysiwygImages;
            $path = $this->getStorage()->getSession()->getCurrentPath();
            foreach ($files as $file) {
                $file = $helper->idDecode($file);
                $_filePath = realpath($path . DIRECTORY_SEPARATOR . $file);
                if (strpos($_filePath, realpath($path)) === 0 &&
                    strpos($_filePath, realpath($helper->getStorageRoot())) === 0
                ) {
                    $this->getStorage()->deleteFile($path . DIRECTORY_SEPARATOR . $file);
                }
            }
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));
        }
    }
}
