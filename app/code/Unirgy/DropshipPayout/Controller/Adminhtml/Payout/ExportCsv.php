<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

class ExportCsv extends AbstractPayout
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'payouts.csv';
        $content = $this->_view->getLayout()->getBlock('udropship.vendor.payout.grid');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
    }
}
