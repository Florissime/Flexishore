<?php

namespace Unirgy\DropshipMicrosite\Observer;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipMicrosite\Model\RegistrationFactory;

class UdropshipVendorSaveBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * @var RegistrationFactory
     */
    protected $_registrationFactory;

    /**
     * @var Encryptor
     */
    protected $_encryptor;

    protected $_registry;

    public function __construct(
        RegistrationFactory $registrationFactory,
        Encryptor $encryptor,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Unirgy\DropshipMicrosite\Helper\Data $micrositeHelper,
        \Unirgy\Dropship\Helper\Item $helperItem
    )
    {
        $this->_registrationFactory = $registrationFactory;
        $this->_encryptor = $encryptor;
        $this->_registry = $registry;

        parent::__construct($scopeConfig, $httpHeader, $udropshipHelper, $micrositeHelper, $helperItem);
    }

    /**
    * Remember vendor password from submitted form
    *
    * @param mixed $observer
    */
    public function execute(Observer $observer)
    {
        $vendor = $observer->getEvent()->getVendor();
        if ($vendor->getPassword()) {
            $this->_registry->register('vendor_password', $vendor->getPassword(), true);
        } elseif ($vendor->getRegId()) {
            $reg = $this->_registrationFactory->create()->load($vendor->getRegId());
            $this->_registry->register('vendor_password', $this->_encryptor->decrypt($reg->getPasswordEnc()), true);
        }
    }
}
