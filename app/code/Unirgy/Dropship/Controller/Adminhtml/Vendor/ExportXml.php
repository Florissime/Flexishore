<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class ExportXml extends AbstractVendor
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'vendors.xml';
        $content = $this->_view->getLayout()->getBlock('udropship.vendor.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
