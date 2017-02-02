<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Downloadable;

use Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Links as DownloadableLinks;
use Magento\Downloadable\Model\Link;
use Magento\MediaStorage\Helper\File\Storage\Database;

class Links extends DownloadableLinks
{
    public function getConfigJson($type='links')
    {
        $this->getConfig()->setUrl(
            $this->_urlBuilder->addSessionParam()
                ->getUrl('udprod/vendor/downloadableUpload', ['type' => $type])
        );
        $this->getConfig()->setParams(['form_key' => $this->getFormKey()]);
        $this->getConfig()->setFileField($type);
        $this->getConfig()->setFilters([
            'all'    => [
                'label' => __('All Files'),
                'files' => ['*.*']
            ]
        ]);
        $this->getConfig()->setReplaceBrowseWithRemove(true);
        $this->getConfig()->setWidth('32');
        $this->getConfig()->setHideUploadButton(true);
        return $this->_jsonEncoder->encode($this->getConfig()->getData());
    }
}