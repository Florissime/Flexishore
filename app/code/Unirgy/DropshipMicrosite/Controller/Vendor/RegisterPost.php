<?php

namespace Unirgy\DropshipMicrosite\Controller\Vendor;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrosite\Model\Source as ModelSource;
use Unirgy\Dropship\Model\Source;

class RegisterPost extends AbstractVendor
{
    public function execute()
    {
        $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
        $hlp = $this->_msHlp;
        try {
            $data = $this->getRequest()->getParams();
            $session->setRegistrationFormData($data);
            $this->checkCaptcha();
            /** @var \Unirgy\DropshipMicrosite\Model\Registration $reg */
            $reg = $this->_hlp->createObj('\Unirgy\DropshipMicrosite\Model\Registration')
                ->setData($data)
                ->validate()
                ->save();
            if (!$this->_scopeConfig->getValue('udropship/microsite/auto_approve', ScopeInterface::SCOPE_STORE)) {
                $hlp->sendVendorSignupEmail($reg);
            }
            $hlp->sendVendorRegistration($reg);
            $session->unsRegistrationFormData();
            if ($this->_scopeConfig->getValue('udropship/microsite/auto_approve', ScopeInterface::SCOPE_STORE)) {
                $vendor = $reg->toVendor();
                $vendor->setStatus(Source::VENDOR_STATUS_INACTIVE);
                if ($this->_scopeConfig->getValue('udropship/microsite/auto_approve', ScopeInterface::SCOPE_STORE)==ModelSource::AUTO_APPROVE_YES_ACTIVE
                ) {
                    $vendor->setStatus(Source::VENDOR_STATUS_ACTIVE);
                }
                $_FILES = [];
                if (!$this->_scopeConfig->isSetFlag('udropship/microsite/skip_confirmation', ScopeInterface::SCOPE_STORE)) {
                    $vendor->setSendConfirmationEmail(1);
                    $vendor->save();
                    $this->messageManager->addSuccess(__('Thank you for application. Instructions were sent to your email to confirm it'));
                } else {
                    $vendor->save();
                    ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->loginById($vendor->getId());
                    if (!$this->_getVendorSession()->getBeforeAuthUrl()) {
                        $this->_getVendorSession()->setBeforeAuthUrl($this->_url->getUrl('udropship'));
                    }
                }
            } else {
                $this->messageManager->addSuccess(__('Thank you for application. As soon as your registration has been verified, you will receive an email confirmation'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            if ($this->getRequest()->getParam('quick')) {
                return $this->_redirect('udropship/vendor/login');
            } else {
                return $this->_redirect('*/*/register');
            }
        }
        return $this->_loginPostRedirect();
    }
}
