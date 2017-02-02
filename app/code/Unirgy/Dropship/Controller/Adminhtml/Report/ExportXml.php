<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

class ExportXml extends AbstractReport
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'shipment_report.xml';
        $content = $this->_view->getLayout()->getBlock('udropship_reportgrid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
