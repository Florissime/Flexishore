<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\LayoutFactory;

class ExportXml extends AbstractStatement
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'statements.xml';
        $content = $this->_view->getLayout()->getBlock('udropship.vendor.statement.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getExcelFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
