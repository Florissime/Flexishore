<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipMicrosite
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipMicrosite\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\VendorFactory;

class Registration extends AbstractModel
{
    /**
     * @var HelperData
     */
    protected $_mspHlp;

    /**
     * @var DropshipMicrositeHelperData
     */
    protected $_msHlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var Encryptor
     */
    protected $_encryptor;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        Registry $registry, 
        HelperData $udropshipHelper,
        DropshipMicrositeHelperData $dropshipMicrositeHelperData, 
        ScopeConfigInterface $configScopeConfigInterface, 
        VendorFactory $modelVendorFactory, 
        Encryptor $encryptionEncryptor, 
        StoreManagerInterface $modelStoreManagerInterface, 
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_msHlp = $dropshipMicrositeHelperData;
        $this->scopeConfig = $configScopeConfigInterface;
        $this->_vendorFactory = $modelVendorFactory;
        $this->_encryptor = $encryptionEncryptor;
        $this->_storeManager = $modelStoreManagerInterface;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Unirgy\DropshipMicrosite\Model\ResourceModel\Registration');
        parent::_construct();
    }
/*
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_helperData->loadCustomData($this);
    }
*/
    public function validate()
    {
        $hlp = $this->_msHlp;
        $dhlp = $this->_hlp;
        extract($this->getData());
        
        $_isQuickRegister = $this->scopeConfig->getValue('udropship/microsite/allow_quick_register', ScopeInterface::SCOPE_STORE);

        if (!isset($vendor_name) || !isset($telephone) || !isset($email) ||
            !isset($password) || !isset($password_confirm)
        ) {
            throw new \Exception(__('Incomplete form data'));
        }
        if (!$_isQuickRegister) {
            if (!isset($carrier_code) || !isset($url_key)
                || !isset($street1) || !isset($city) || !isset($country_id)
            ) {
                throw new \Exception(__('Incomplete form data'));
            }
        }
        if ($password!=$password_confirm) {
            throw new \Exception(__('Passwords do not match'));
        }
        $collection = $this->_vendorFactory->create()->getCollection()
            ->addFieldToFilter('email', $email);
        foreach ($collection as $dup) {
            if ($this->scopeConfig->getValue('udropship/vendor/unique_email', ScopeInterface::SCOPE_STORE)) {
                throw new \Exception(__('A vendor with supplied email already exists.'));
            }
            if ($this->_encryptor->validateHash($password, $dup->getPasswordHash())) {
                throw new \Exception(__('A vendor with supplied email and password already exists.'));
            }
        }
        if (isset($url_key)) {
            $vendor = $this->_vendorFactory->create()->load($url_key, 'url_key');
            if ($vendor->getId()) {
                throw new \Exception(__('This subdomain is already taken, please choose another.'));
            }
            if ($this->_hlp->isUrlKeyReserved($url_key)) {
                throw new \Exception(__('This URL Key is reserved. Please choose another.'));
            }
        }
        if ($this->scopeConfig->getValue('udropship/vendor/unique_vendor_name', ScopeInterface::SCOPE_STORE)) {
            $collection = $this->_vendorFactory->create()->getCollection()
                ->addFieldToFilter('vendor_name', $vendor_name);
            foreach ($collection as $dup) {
                throw new \Exception(__('A vendor with supplied name already exists.'));
            }
        }
        $this->setStreet(@$street1."\n".@$street2);
        $this->setPasswordEnc($this->_encryptor->encrypt($password));
        $this->setPasswordHash($this->_encryptor->getHash($password, 2));
        $this->unsPassword();
        $this->setRemoteIp($_SERVER['REMOTE_ADDR']);
        $this->setRegisteredAt($this->_hlp->now());
        $this->setStoreId($this->_storeManager->getStore()->getId());
        $dhlp->processCustomVars($this);

        return $this;
    }

    protected $_inAfterSave;

    public function afterSave()
    {
        if ($this->_inAfterSave) {
            return $this;
        }
        $this->_inAfterSave = true;

        parent::afterSave();

        if (!empty($_FILES)) {

            /** @var \Magento\Framework\App\Filesystem\DirectoryList $dirList */
            $dirList = $this->_hlp->getObj('\Magento\Framework\App\Filesystem\DirectoryList');
            $baseDir = $dirList->getPath('media');
            $vendorDir = 'registration'.DIRECTORY_SEPARATOR.$this->getId();
            $vendorAbsDir = $baseDir.DIRECTORY_SEPARATOR.$vendorDir;
            /* @var \Magento\Framework\Filesystem\Directory\Write $dirWrite */
            $dirWrite = $this->_hlp->createObj('\Magento\Framework\Filesystem\Directory\WriteFactory')->create($baseDir);
            $dirWrite->create($vendorDir);

            foreach ($_FILES as $k=>$img) {
                if (empty($img['tmp_name']) || empty($img['name']) || empty($img['type'])) {
                    continue;
                }
                if (!@move_uploaded_file($img['tmp_name'], $vendorAbsDir.DIRECTORY_SEPARATOR.$img['name'])) {
                    throw new \Exception('Error while uploading file: '.$img['name']);
                }
                $this->setData($k, 'registration/'.$this->getId().'/'.$img['name']);
            }
            $this->save();
        }
        $this->_inAfterSave = false;
        return $this;
    }

    public function toVendor()
    {
        $vendor = $this->_vendorFactory->create()->load($this->scopeConfig->getValue('udropship/microsite/template_vendor', ScopeInterface::SCOPE_STORE));
        $carrierCode = $this->getCarrierCode() ? $this->getCarrierCode() : $vendor->getCarrierCode();
        $vendor->getShippingMethods();
        $vendor->unsetData('vendor_name');
        $vendor->unsetData('confirmation_sent');
        $vendor->unsetData('url_key');
        $vendor->unsetData('email');
        $vendor->addData($this->getData());
        $vendor->setCarrierCode($carrierCode);
        $this->_hlp->loadCustomData($vendor);
        $vendor->setPassword($this->_encryptor->decrypt($this->getPasswordEnc()));
        $vendor->unsVendorId();
        $shipping = $vendor->getShippingMethods();
        $postedShipping = [];
        foreach ($shipping as $sId=>&$_s) {
            foreach ($_s as &$s) {
                if ($s['carrier_code']==$vendor->getCarrierCode()) {
                    $s['carrier_code'] = null;
                }
                unset($s['vendor_shipping_id']);
                $s['on'] = true;
                $postedShipping[$s['shipping_id']] = $s;
            }
        }
        unset($_s);
        unset($s);
        $vendor->setPostedShipping($postedShipping);
        $vendor->setShippingMethods($shipping);
        return $vendor;
    }
}