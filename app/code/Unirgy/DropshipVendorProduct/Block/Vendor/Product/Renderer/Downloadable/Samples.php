<?php

namespace Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\Downloadable;

use Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Samples as DownloadableSamples;
use Magento\Downloadable\Model\Sample;
use Magento\MediaStorage\Helper\File\Storage\Database;

class Samples extends DownloadableSamples
{
    public function getConfigJson()
    {
        $this->getConfig()->setUrl(
            $this->_urlBuilder->addSessionParam()
                ->getUrl('udprod/vendor/downloadableUpload', ['type' => 'samples'])
        );
        $this->getConfig()->setParams(['form_key' => $this->getFormKey()]);
        $this->getConfig()->setFileField('samples');
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