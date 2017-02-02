<?php

namespace Unirgy\DropshipVendorProduct\Controller\Vendor;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Sample;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\File\Uploader;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;

class DownloadableUpload extends AbstractVendor
{
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        $tmpPath = '';
        if ($type == 'samples') {
            $tmpPath = Sample::getBaseTmpPath();
        } elseif ($type == 'links') {
            $tmpPath = Link::getBaseTmpPath();
        } elseif ($type == 'link_samples') {
            $tmpPath = Link::getBaseSampleTmpPath();
        }
        $result = [];
        try {
            $uploader = new Uploader($type);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $result = $uploader->save($tmpPath);

            /**
             * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
             */
            $result['tmp_name'] = str_replace(DIRECTORY_SEPARATOR, "/", $result['tmp_name']);
            $result['path'] = str_replace(DIRECTORY_SEPARATOR, "/", $result['path']);

            /*
            if (isset($result['file'])) {
                $fullPath = rtrim($tmpPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($result['file'], DIRECTORY_SEPARATOR);
                $this->_storageDatabase->saveFile($fullPath);
            }
            */

            $result['cookie'] = [
                'name'     => session_name(),
                'value'    => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path'     => $this->_getSession()->getCookiePath(),
                'domain'   => $this->_getSession()->getCookieDomain()
            ];
        } catch (\Exception $e) {
            $result = ['error'=>$e->getMessage(), 'errorcode'=>$e->getCode()];
        }

        $this->_resultRawFactory->create()->setContents($this->_jsonEncoderInterface->jsonEncode($result));
    }
}
