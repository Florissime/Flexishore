<?php

namespace Unirgy\DropshipMicrosite\Controller\Vendor;

use Magento\Framework\App\ObjectManager;

class Register extends AbstractVendor
{

    public function execute()
    {
        ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->setData('umicrosite_registration_form_show_captcha',1);
        return $this->_renderPage(null, 'register');
    }
}
