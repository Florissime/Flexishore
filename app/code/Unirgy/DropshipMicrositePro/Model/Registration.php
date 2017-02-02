<?php

namespace Unirgy\DropshipMicrositePro\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrositePro\Helper\Data as DropshipMicrositeProHelperData;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\DropshipMicrosite\Model\Registration as ModelRegistration;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\VendorFactory;

class Registration extends ModelRegistration
{
    /**
     * @var DropshipMicrositeProHelperData
     */
    protected $_mspHlp;

    public function __construct(
        DropshipMicrositeProHelperData $micrositeProHelper,
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
        $this->_mspHlp = $micrositeProHelper;

        parent::__construct($context, $registry, $udropshipHelper, $dropshipMicrositeHelperData, $configScopeConfigInterface, $modelVendorFactory, $encryptionEncryptor, $modelStoreManagerInterface, $resource, $resourceCollection, $data);
    }

    public function getRegFields()
    {
        return $this->_mspHlp->getRegFields();
    }
    public function attachLabelVars()
    {
        foreach ($this->getRegFields() as $name=>$rf) {
            switch ($rf['type']) {
                case 'statement_po_type': case 'payout_po_status_type': case 'notify_lowstock':
                case 'select': case 'multiselect': case 'checkboxes':
                    $srcModel = $rf['source_model'];
                    $source = ObjectManager::getInstance()->get($srcModel);
                    if (is_callable([$source, 'setPath'])) {
                        $source->setPath(!empty($rf['source']) ? $rf['source'] : $name);
                    }
                    if ($rf['type']=='multiselect') {
                        $msValues = $this->getData($name);
                        if (!is_array($msValues)) {
                            $msValues = explode(',', $msValues);
                        }
                        $values = array_map('trim', $msValues);
                    } else {
                        $values = $this->getData($name);
                    }
                    $values = array_filter((array)$values);
                    if (!empty($values) && is_callable([$source, 'getOptionLabel'])) {
                        $lblValues = [];
                        foreach ($values as $value) {
                            $lblValues[] = $source->getOptionLabel($value);
                        }
                        $lblValues = implode(', ', $lblValues);
                        $this->setData($name.'_label', $lblValues);
                    }
                    break;
            }
        }
    }

    public function validate()
    {
        $hlp = $this->_msHlp;
        $dhlp = $this->_hlp;
        extract($this->getData());

        $hasPasswordField = false;
        foreach ($this->getRegFields() as $rf) {
            $rfName = str_replace('[]', '', $rf['name']);
            if (!empty($rf['required'])
                && in_array($this->getData($rfName), ['',null], true)
                && !in_array($rf['type'], ['image','file'])
                && !in_array($rfName, ['payout_paypal_email'])
            ) {
                if ($rfName != 'region_id' || !$this->getData('region')) {
                    throw new \Exception(__('Incomplete form data'));
                }
            }
            $hasPasswordField = $hasPasswordField || in_array($rfName, ['password_confirm','password']);
            if ($rfName=='password_confirm'
                && $this->getData('password') != $this->getData('password_confirm')
            ) {
                throw new \Exception(__('Passwords do not match'));
            }
        }

        $this->setStreet(@$street1."\n".@$street2);
        $this->initPassword(@$password);
        $this->initUrlKey(@$url_key);
        $this->setRemoteIp($_SERVER['REMOTE_ADDR']);
        $this->setRegisteredAt($this->_hlp->now());
        $this->setStoreId($this->_storeManager->getStore()->getId());
        $dhlp->processCustomVars($this);
        $this->attachLabelVars();

        return $this;
    }
    public function initPassword($password=null)
    {
        if (empty($password)) {
            $password = $this->generatePassword();
        }
        $this->setPasswordEnc($this->_encryptor->encrypt($password));
        $this->setPasswordHash($this->_encryptor->getHash($password, 2));
        $this->unsPassword();
        return $this;
    }
    public function formatUrlKey($str)
    {
        /* @var \Magento\Framework\Filter\FilterManager $filter */
        $filter = $this->_hlp->getObj('\Magento\Framework\Filter\FilterManager');
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', $filter->translitUrl($str));
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');

        return $urlKey;
    }
    public function initUrlKey($urlKey=null)
    {
        if (empty($urlKey)) {
            $urlKey = $this->formatUrlKey($this->getData('vendor_name'));
        }
        $this->setData('url_key', $urlKey);
        return $this;
    }
    protected function generatePassword()
    {
        return $this->_mspHlp->processRandomPattern('[AN*6]');
    }

    public function beforeSave()
    {
        parent::beforeSave();
        $this->_hlp->processCustomVars($this);
        return $this;
    }

}