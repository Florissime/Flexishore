<?php

namespace Unirgy\Dropship\Controller\Vendor;


class PasswordPost extends AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        $hlp = $this->_hlp;
        try {
            $r = $this->getRequest();
            if (($confirm = $r->getParam('confirm'))) {
                $password = $r->getParam('password');
                $passwordConfirm = $r->getParam('password_confirm');
                /** @var \Unirgy\Dropship\Model\Vendor $vendor */
                $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($confirm, 'random_hash');
                if (!$password || !$passwordConfirm || $password!=$passwordConfirm || !$vendor->getId()) {
                    $this->messageManager->addError('Invalid form data');
                    return $this->resultRedirectFactory->create()->setPath('*/*/password', array('confirm'=>$confirm));
                }
                $vendor->setPassword($password)->unsRandomHash()->save();
                $session->loginById($vendor->getId());
                $this->messageManager->addSuccess(__('Your password has been reset.'));
                return $this->resultRedirectFactory->create()->setPath('*/*');
            } elseif (($email = $r->getParam('email'))) {
                if ($hlp->sendPasswordResetEmail($email)) {
                    $this->messageManager->addSuccess(__('Thank you, password reset instructions have been sent to the email you have provided, if a vendor with such email exists.'));
                } else {
                    $this->messageManager->addSuccess(__('No records found in the system that match the email'));
                }
                return $this->resultRedirectFactory->create()->setPath('*/*/login');
            } else {
                $this->messageManager->addError(__('Invalid form data'));
                return $this->resultRedirectFactory->create()->setPath('*/*/password');
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/password');
        }
    }
}
