<?php

namespace Unirgy\DropshipMicrositePro\Controller\Vendor;

class CheckEmailUnique extends AbstractVendor
{
    public function execute()
    {
        $email = $this->getRequest()->getParam('email');
        if (empty($email)) {
            return $this->returnResult([
                'error'=>true,
                'success'=>false,
                'message'=>'Empty email'
            ]);
        } else {
            if (!$this->_mspHlp->checkEmailUnique($email)) {
                return $this->returnResult([
                    'error'=>true,
                    'success'=>false,
                    'message'=>'Email is used'
                ]);
            } else {
                return $this->returnResult([
                    'error'=>false,
                    'success'=>true,
                    'message'=>'Email is not used'
                ]);
            }
        }
    }
}
