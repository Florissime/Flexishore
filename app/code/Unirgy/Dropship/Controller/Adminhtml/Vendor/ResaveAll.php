<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

use \Unirgy\Dropship\Model\Vendor;

class ResaveAll extends AbstractVendor
{
    public function execute()
    {
        ob_implicit_flush();
        echo 'START. ';
        $vendors = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->getCollection();
        foreach ($vendors as $vendor) {
            echo $vendor->getId().', ';
            $vendor->afterLoad();
            $vendor->save();
        }
        echo 'DONE.';
        exit;
    }
}
