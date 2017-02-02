<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;

class ExportCsv extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'statements.csv';
        $content = $this->_view->getLayout()->getBlock('udropship.vendor.statement.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
