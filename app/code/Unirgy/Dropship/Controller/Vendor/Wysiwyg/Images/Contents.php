<?php

namespace Unirgy\Dropship\Controller\Vendor\Wysiwyg\Images;

use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Wysiwyg\Images;
use Unirgy\Dropship\Model\Wysiwyg\Images\StorageFactory;

class Contents extends AbstractImages
{
    public function execute()
    {
        try {
            $this->_initAction()->_saveSessionCurrentPath();
            $this->_view->addActionLayoutHandles();
            return $this->_resultRawFactory->create()->setContents($this->_view->getLayout()->getOutput());
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            return $this->_resultRawFactory->create()->setContents($this->_hlp->jsonEncode($result));
        }
    }
}
