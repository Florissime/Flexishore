<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Report;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;

class ItemExportXml extends AbstractReport
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'shipment_item_report.xml';
        $content = $this->_view->getLayout()->getBlock('udropship_item_reportgrid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
