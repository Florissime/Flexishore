<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

class ExportCsv extends AbstractReport
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'shipment_report.csv';
        $content = $this->_view->getLayout()->getBlock('udropship_reportgrid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
