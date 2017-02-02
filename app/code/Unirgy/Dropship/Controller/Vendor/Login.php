<?php

namespace Unirgy\Dropship\Controller\Vendor;


class Login extends AbstractVendor
{
    public function execute()
    {
        if ($this->_hlp->session()->isLoggedIn()) {
            return $this->_resultForwardFactory->create()->forward('index');
        } else {
            $ajax = $this->getRequest()->getParam('ajax');
            if ($ajax) {
                $this->messageManager->addError(__('Your session has been expired. Please log in again.'));
            }
            return $this->_renderPage($ajax ? 'udropship_vendor_login_ajax' : null);
        }
    }
}
