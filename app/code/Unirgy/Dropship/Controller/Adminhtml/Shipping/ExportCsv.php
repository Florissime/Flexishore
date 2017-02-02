<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class ExportCsv extends AbstractShipping
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'shipping.csv';
        $content = $this->_view->getLayout()->getBlock('udropship.shipping.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
