<?php

namespace Unirgy\DropshipMicrositePro\Controller\Vendor;

class CheckUrlkeyUnique extends AbstractVendor
{
    public function execute()
    {
        $urlkey = $this->getRequest()->getParam('urlkey');
        if (empty($urlkey)) {
            return $this->returnResult([
                'error'=>true,
                'success'=>false,
                'message'=>'Empty Url Key'
            ]);
        } else {
            if (!$this->_mspHlp->checkUrlkeyUnique($urlkey)) {
                return $this->returnResult([
                    'error'=>true,
                    'success'=>false,
                    'message'=>'Url key is used'
                ]);
            } else {
                return $this->returnResult([
                    'error'=>false,
                    'success'=>true,
                    'message'=>'Url key is not used'
                ]);
            }
        }
    }
}
