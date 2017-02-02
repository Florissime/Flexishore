<?php

namespace Unirgy\DropshipMicrositePro\Controller\Vendor;

class CheckVendorNameUnique extends AbstractVendor
{
    public function execute()
    {
        $vendor_name = $this->getRequest()->getParam('vendor_name');
        if (empty($vendor_name)) {
            return $this->returnResult([
                'error'=>true,
                'success'=>false,
                'message'=>'Empty Shop Name'
            ]);
        } else {
            if (!$this->_mspHlp->checkVendorNameUnique($vendor_name)) {
                return $this->returnResult([
                    'error'=>true,
                    'success'=>false,
                    'message'=>'Shop Name is used'
                ]);
            } else {
                return $this->returnResult([
                    'error'=>false,
                    'success'=>true,
                    'message'=>'Shop Name is not used'
                ]);
            }
        }
    }
}
