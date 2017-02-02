<?php

namespace Unirgy\DropshipMicrositePro\Controller\Vendor;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\DesignInterface;
use Unirgy\Dropship\Model\VendorFactory;

class Confirm extends AbstractVendor
{
    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var Encryptor
     */
    protected $_encryptor;

    public function __construct(
        Encryptor $encryptor,
        VendorFactory $vendorFactory,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesignInterface,
        RawFactory $resultRawFactory,
        EncoderInterface $jsonEncoder,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\DropshipMicrositePro\Helper\Data $micrositeProHelper,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_vendorFactory = $vendorFactory;
        $this->_encryptor = $encryptor;

        parent::__construct($context, $scopeConfig, $viewDesignInterface, $resultRawFactory, $jsonEncoder, $micrositeHelper, $micrositeProHelper, $udropshipHelper, $resultPageFactory);
    }

    public function execute()
    {
        if ($this->_getSession()->isLoggedIn()) {
            return $this->_redirect('udropship/vendor/');
        }
        try {
            $id      = $this->getRequest()->getParam('id', false);
            $key     = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);
            if (empty($id) || empty($key)) {
                throw new \Exception(__('Bad request.'));
            }
            try {
                $vendor = $this->_vendorFactory->create()->load($id);
                if ((!$vendor) || (!$vendor->getId())) {
                    throw new \Exception('Failed to load vendor by id.');
                }
                if ($vendor->getConfirmation() !== $key) {
                    throw new \Exception(__('Wrong confirmation key.'));
                }

                // activate customer
                try {
                    $vendor->setConfirmation(null);
                    if (!$this->_mspHlp->isPassFieldInForm()) {
                        $password = $this->_mspHlp->processRandomPattern('[AN*6]');
                        $vendor->setPassword($password);
                        $vendor->setPasswordEnc($this->_encryptor->encrypt($password));
                        $vendor->setPasswordHash($this->_encryptor->getHash($password, 2));
                        $this->_hlp->rHlp()->updateModelFields($vendor, ['confirmation','password_hash','password_enc']);
                    } else {
                        $this->_hlp->rHlp()->updateModelFields($vendor, ['confirmation']);
                    }
                }
                catch (\Exception $e) {
                    throw new \Exception(__('Failed to confirm vendor account.'));
                }

                $this->_msHlp->sendVendorWelcomeEmail($vendor);
                $vendor->setPassword('');
                $vendor->setPasswordEnc('');
                $this->_hlp->rHlp()->updateModelFields($vendor, ['password', 'password_enc']);
                $this->messageManager->addSuccess("You've successfully confirmed your account. Please check your mailbox for email with your account information in order to login.");
                return $this->_redirect('udropship/vendor/');
            }
            catch (\Exception $e) {
                throw new \Exception(__('Wrong vendor account specified.'));
            }
        }
        catch (\Exception $e) {
            // die unhappy
            $this->messageManager->addError($e->getMessage());
            return $this->_redirect('udropship/vendor/');
        }
    }
}
