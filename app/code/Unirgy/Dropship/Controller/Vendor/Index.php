<?php

namespace Unirgy\Dropship\Controller\Vendor;

class Index extends AbstractVendor
{
    public function execute()
    {
        $_hlp = $this->_hlp;
        if ($_hlp->isUdpoActive() && !is_subclass_of($this, '\Unirgy\DropshipPo\Controller\Vendor\AbstractVendor')) {
            return $this->resultRedirectFactory->create()->setPath('udpo/vendor/index');
        }
        switch ($this->getRequest()->getParam('submit_action')) {
        case 'labelBatch':
        case __('Create and Download Labels Batch'):
            return $this->_forward('labelBatch');

        case 'existingLabelBatch':
            return $this->_forward('existingLabelBatch');

        case 'packingSlips':
        case __('Download Packing Slips'):
            return $this->_forward('packingSlips');

        case 'updateShipmentsStatus':
            return $this->_forward('updateShipmentsStatus');
        case 'udbatchExport':
            return $this->_forward('exportShipments', 'vendor_batch', 'udbatch');
        }

        return $this->_renderPage(null, 'dashboard');
    }
}
