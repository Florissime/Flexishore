<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;

class ExportXml extends AbstractShipping
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'shipping.xml';
        $content = $this->_view->getLayout()->getBlock('udropship.shipping.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
