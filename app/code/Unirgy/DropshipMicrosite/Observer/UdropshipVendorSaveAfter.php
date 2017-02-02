<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Authorization\Model\RoleFactory;
use Magento\User\Model\UserFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrosite\Model\RegistrationFactory;
use Unirgy\Dropship\Model\Source;

class UdropshipVendorSaveAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * @var UserFactory
     */
    protected $_userFactory;

    /**
     * @var Encryptor
     */
    protected $_encryptor;

    /**
     * @var RoleFactory
     */
    protected $_roleFactory;

    /**
     * @var RegistrationFactory
     */
    protected $_registrationFactory;

    public function __construct(
        UserFactory $modelUserFactory, 
        Encryptor $encryptionEncryptor, 
        RoleFactory $modelRoleFactory, 
        RegistrationFactory $modelRegistrationFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->_userFactory = $modelUserFactory;
        $this->_encryptor = $encryptionEncryptor;
        $this->_roleFactory = $modelRoleFactory;
        $this->_registrationFactory = $modelRegistrationFactory;
        $this->_registry = $registry;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    /**
    * Synchronize admin user from vendor object
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $vendor = $observer->getEvent()->getVendor();
        $vendorPassword = $this->_registry->registry('vendor_password');

        $user = $this->_userFactory->create()->load($vendor->getId(), 'udropship_vendor');
        $changed = false;
        $nameChanged = false;
        $new = false;
        if (!$user->getId()) {
            $new = true;
            $user->setData([
                'udropship_vendor' => $vendor->getId(),
                'is_active' => 1,
            ]);
        }
        if (!$new && $vendor->getVendorName()!=$user->getFirstname()) {
            $nameChanged = true;
        }
        $vendorLastname = $vendor->getVendorAttn()?$vendor->getVendorAttn():$vendor->getVendorName();
//        $isActive = $vendor->getStatus()=='A' ? 1 : 0;
        if ($new
            || $vendor->getVendorName()!=$user->getFirstname()
            || $vendorLastname!=$user->getLastname()
            || $vendor->getEmail()!=$user->getEmail()
//            || $isActive!=$user->getIsActive()
            ) {
            $user->addData([
                'firstname' => $vendor->getVendorName(),
                'lastname'  => $vendorLastname,
                'email'     => $vendor->getEmail(),
                'username'  => $vendor->getEmail(),
                'password'  => $vendor->getPasswordHash()
//                'is_active' => $isActive,
            ]);
            $roles = $this->_roleFactory->create()->getCollection()
                ->addFieldToFilter('role_name', 'Dropship Vendor')
                ->addFieldToFilter('parent_id', 0);
            foreach ($roles as $role) {
                $user->setRoleId($role->getRoleId());
                break;
            }
            $changed = true;
        }
        if (!$new) {
            if (!$this->_encryptor->validateHash($vendorPassword, $user->getPassword())) {
                $user->setNewPassword($vendorPassword);
                $changed = true;
            } else {
                $user->unsPassword();
            }
        }
        if ($changed) {
            $user->save();
        }

        if (!$new && $nameChanged) {
            $roles = $this->_roleFactory->create()->getCollection()
                ->addFieldToFilter('user_id', $user->getId());
            foreach ($roles as $role) {
                $role->setRoleName($vendor->getVendorName())->save();
            }
        }

        if ($vendor->getRegId()) {
            if ((!$this->_hlp->isModuleActive('Unirgy_DropshipMicrositePro')
                || $this->scopeConfig->isSetFlag('udropship/microsite/skip_confirmation', ScopeInterface::SCOPE_STORE)
                || !$vendor->getSendConfirmationEmail()
                ) && !in_array($vendor->getStatus(), [Source::VENDOR_STATUS_REJECTED, Source::VENDOR_STATUS_PENDINGMEMBER])
            ) {
                $vendor->setPassword($vendorPassword);
                $this->_msHlp->sendVendorWelcomeEmail($vendor);
                $vendor->setPassword('');
                $vendor->setPasswordEnc('');
                $this->_hlp->rHlp()->updateModelFields($vendor, ['password', 'password_enc']);
            }
            $this->_registrationFactory->create()->load($vendor->getRegId())->delete();
        }
        if ($this->_hlp->isModuleActive('Unirgy_DropshipMicrositePro')) {
            if ($vendor->getSendConfirmationEmail()) {
                $vendor->setConfirmation(md5(uniqid()));
                $vendor->setConfirmationSent(1);
                $this->_hlp->rHlp()->updateModelFields($vendor, ['confirmation', 'confirmation_sent']);
                $this->_hlp->getObj('\Unirgy\DropshipMicrositePro\Helper\Data')->sendVendorConfirmationEmail($vendor);
            } elseif ($vendor->getSendRejectEmail()) {
                $this->_hlp->getObj('\Unirgy\DropshipMicrositePro\Helper\Data')->sendVendorRejectEmail($vendor);
            }
        }
    }
}
