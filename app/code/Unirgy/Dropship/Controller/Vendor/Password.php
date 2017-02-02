<?php

namespace Unirgy\Dropship\Controller\Vendor;

class Password extends AbstractVendor
{
    public function execute()
    {
        $session = $this->_hlp->session();
        $hlp = $this->_hlp;
        $confirm = $this->getRequest()->getParam('confirm');
        if ($confirm) {
            /** @var \Unirgy\Dropship\Model\Vendor $vendor */
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor')->load($confirm, 'random_hash');
            if ($vendor->getId()) {
                $this->_registry->register('reset_vendor', $vendor);
            } else {
                $this->messageManager->addError(__('Invalid confirmation link'));
            }
        }
        return $this->_renderPage();
    }
}
