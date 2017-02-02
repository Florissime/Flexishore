<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class ExportCsv extends AbstractVendor
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'vendors.csv';
        $content = $this->_view->getLayout()->getBlock('udropship.vendor.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
