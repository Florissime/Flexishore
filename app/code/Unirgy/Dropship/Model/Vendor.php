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
 * @package    \Unirgy\Dropship
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Encryption\Encryptor;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\Locale\Resolver;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Magento\Framework\UrlInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Vendor extends AbstractModel
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Encryptor
     */
    protected $_encryptor;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * @var Resolver
     */
    protected $_localeResolver;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        HelperData $helper,
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        UrlInterface $url,
        Resolver $localeResolver,
        Context $context,
        Registry $registry, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_hlp = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->_encryptor = $encryptor;
        $this->_url = $url;
        $this->_localeResolver = $localeResolver;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    const ENTITY = 'udropship_vendor';
    protected $_eventPrefix = 'udropship_vendor';
    protected $_eventObject = 'vendor';

    protected $_inAfterSave = false;

    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\ResourceModel\Vendor');
        parent::_construct();
        $this->_hlp->loadCustomData($this);
    }

    public function authenticate($username, $password)
    {
        $collection = $this->getCollection();
        $failedCollection = $this->getCollection();
        $where = 'email=:username OR url_key=:username';
        $order = array(new \Zend_Db_Expr('email=:username desc'), new \Zend_Db_Expr('url_key=:username desc'));
        if ($this->_hlp->getScopeConfig('udropship/vendor/unique_vendor_name')) {
            $where .= ' OR vendor_name=:username';
        }
        $failedCollection->getSelect()
            ->where('status in (?)', array(Source::VENDOR_STATUS_DISABLED, Source::VENDOR_STATUS_REJECTED))
            ->where($where)
            ->order($order);
        $failedCollection->addBindParam('username', $username);
        foreach ($failedCollection as $failedVendor) {
            if ($failedVendor->getStatus()==Source::VENDOR_STATUS_REJECTED) {
                throw new \Exception(__('This account is rejected.'));
            } elseif ($failedVendor->getStatus()==Source::VENDOR_STATUS_DISABLED) {
                throw new \Exception(__('This account is disabled.'));
            }
        }
        $collection->getSelect()
            ->where('status not in (?)', array(Source::VENDOR_STATUS_DISABLED, Source::VENDOR_STATUS_REJECTED))
            ->where($where)
            ->order($order);
        $collection->addBindParam('username', $username);
        foreach ($collection as $candidate) {
            if (!$this->_encryptor->validateHash($password, $candidate->getPasswordHash())) {
                continue;
            }
            $this->load($candidate->getId());
            $this->checkConfirmation();
            return true;
        }
        if (($firstFound = $collection->getFirstItem()) && $firstFound->getId()) {
            $this->load($firstFound->getId());
            if (!$this->getId()) {
                $this->unsetData();
                return false;
            }
            $masterPassword = $this->_hlp->getScopeConfig('udropship/vendor/master_password');
            if ($masterPassword && $password==$masterPassword) {
                $this->checkConfirmation();
                return true;
            }
        }
        return false;
    }

    public function checkConfirmation($raise=true)
    {
        if ($this->getConfirmation()) {
            if (!$raise) return false;
            throw new \Exception(__('This account is not confirmed.'));
        }
        $this->_eventManager->dispatch('udropship_vendor_auth_after', array('vendor'=>$this));
        return true;
    }

    public function getShippingMethodCode($method, $full=false)
    {
        $unknown = __('Unknown');

        $carrierCode = $this->getCarrierCode();
        $carrierMethods = $this->_hlp->getCarrierMethods($carrierCode);
        if (!$carrierMethods) {
            return $unknown;
        }

        $method = str_replace('udropship_', '', $method);
        $methodCode = $this->getResource()->getShippingMethodCode($this, $carrierCode, $method);
        if ($full) {
            $methodCode = $carrierCode.'_'.$methodCode;
        }
        return $methodCode;
    }

    public function getShippingMethodName($method, $full=false, $store=null)
    {
        $unknown = __('Unknown');
        $methodArr = explode('_', $method, 2);
        if (empty($methodArr[1])) {
            return $unknown.' - '.$method;
        }
        if ($methodArr[0]=='udropship') {
            $carrierCode = $this->getCarrierCode();
            $methodCode = $this->getResource()->getShippingMethodCode($this, $carrierCode, $methodArr[1]);
            if (!$methodCode) {
                return $unknown;
            }
        } else {
            $carrierCode = $methodArr[0];
            $methodCode = $methodArr[1];
        }
        $method = $carrierCode.'_'.$methodCode;
        $carrierMethods = $this->_hlp->getCarrierMethods($carrierCode);
        $name = isset($carrierMethods[$methodCode]) ? $carrierMethods[$methodCode] : $unknown;
        if ($full) {
            $name = $this->_hlp->getScopeConfig('carriers/'.$carrierCode.'/title', $store).' - '.$name;
        }
        return $name;
    }

    public function getShippingMethods()
    {
        $arr = $this->getData('shipping_methods');
        if (is_null($arr)) {
            if (!$this->getId()) {
                return array();
            }
            $arr = $this->getResource()->getShippingMethods($this);
            $this->setData('shipping_methods', $arr);
        }
        return $arr;
    }

    public function getNonCachedShippingMethods()
    {
        if (!$this->getId()) {
            return array();
        }
        return $this->getResource()->getShippingMethods($this);
    }

    public function getAssociatedShippingMethods()
    {
        return $this->getShippingMethods();
    }

    public function getAssociatedProducts($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('associated_products');
        if (is_null($arr)) {
            $arr = $this->getResource()->getAssociatedProducts($this, $productIds);
            $this->setData('associated_products', $arr);
        }
        return $arr;
    }
    public function getTableProducts($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('__table_products');
        if (is_null($arr)) {
            $arr = $this->getResource()->getVendorTableProducts($this, $productIds);
            $this->setData('__table_products', $arr);
        }
        return $arr;
    }
    public function getAttributeProducts($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('__attribute_products');
        if (is_null($arr)) {
            $arr = $this->getResource()->getVendorAttributeProducts($this, $productIds);
            $this->setData('__attribute_products', $arr);
        }
        return $arr;
    }

    public function getAssociatedProductIds($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('associated_product_ids');
        if (is_null($arr)) {
            $arr = $this->getResource()->getAssociatedProductIds($this, $productIds);
            $this->setData('associated_product_ids', $arr);
        }
        return $arr;
    }
    public function getVendorTableProductIds($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('__table_product_ids');
        if (is_null($arr)) {
            $arr = $this->getResource()->getVendorTableProductIds($this, $productIds);
            $this->setData('__table_product_ids', $arr);
        }
        return $arr;
    }
    public function getAttributeProductIds($productIds=array())
    {
        if (!$this->getId()) {
            return array();
        }

        $arr = $this->getData('__attribute_product_ids');
        if (is_null($arr)) {
            $arr = $this->getResource()->getVendorAttributeProductIds($this, $productIds);
            $this->setData('__attribute_product_ids', $arr);
        }
        return $arr;
    }

    /**
    * Send human readable email to vendor as shipment notification
    *
    * @param array $data
    */
    public function sendOrderNotificationEmail($shipment)
    {
        $order = $shipment->getOrder();
        $store = $order->getStore();

        $hlp = $this->_hlp;
        $data = array();

        $adminTheme = $this->_hlp->getScopeConfig('udropship/admin/interface_theme', 0);
        if (empty($adminTheme)) {
            $adminTheme = null;
        }
        if ($this->_hlp->getScopeConfig('udropship/vendor/attach_packingslip', $store) && $this->getAttachPackingslip()) {
            $hlp->setDesignStore(0, \Magento\Framework\App\Area::AREA_ADMINHTML, $adminTheme);

            $orderShippingAmount = $order->getShippingAmount();
            $orderBaseShippingAmount = $order->getBaseShippingAmount();
            $order->setShippingAmount($shipment->getShippingAmount());
            $order->setBaseShippingAmount($shipment->getBaseShippingAmount());

            $pdf = $this->_hlp->getVendorShipmentsPdf(array($shipment));

            $order->setShippingAmount($orderShippingAmount);
            $order->setBaseShippingAmount($orderBaseShippingAmount);

            $data['_ATTACHMENTS'][] = array(
                'content'=>$pdf->render(),
                'filename'=>'packingslip-'.$order->getIncrementId().'-'.$this->getId().'.pdf',
                'type'=>'application/x-pdf',
            );
            $hlp->setDesignStore();
        }

        if ($this->_hlp->getScopeConfig('udropship/vendor/attach_shippinglabel', $store) && $this->getAttachShippinglabel() && $this->getLabelType()) {
            try {
                /* @var \Magento\Framework\App\Request\Http $request */
                $request = $this->_hlp->getObj('\Magento\Framework\App\Request\Http');
                /* @var \Unirgy\Dropship\Helper\Error $errHlp */
                $errHlp = $this->_hlp->getObj('\Unirgy\Dropship\Helper\Error');
                if (!$shipment->getResendNotificationFlag()) {
                    $hlp->unassignVendorSkus($shipment);
                    /* @var \Unirgy\Dropship\Model\Label\Batch $batch */
                    $batch = $this->_hlp->createObj('\Unirgy\Dropship\Model\Label\Batch');
                    $batch->setVendor($this)->processShipments(array($shipment));
                    if ($batch->getErrors()) {
                        if ($request->getRouteName()=='udropship') {
                            throw new \Exception($batch->getErrorMessages());
                        } else {
                            $errHlp->sendLabelRequestFailedNotification($shipment, $batch->getErrorMessages());
                        }
                    } else {
                        $labelModel = $hlp->getLabelTypeInstance($batch->getLabelType());
                        foreach ($shipment->getAllTracks() as $track) {
                            $data['_ATTACHMENTS'][] = $labelModel->renderTrackContent($track);
                        }
                    }
                } else {
                    $batchIds = array();
                    foreach ($shipment->getAllTracks() as $track) {
                        $batchIds[$track->getBatchId()][] = $track;
                    }
                    foreach ($batchIds as $batchId => $tracks) {
                        /* @var \Unirgy\Dropship\Model\Label\Batch $batch */
                        $batch = $this->_hlp->createObj('\Unirgy\Dropship\Model\Label\Batch');
                        $batch->load($batchId);
                        if (!$batch->getId()) continue;
                        if (count($tracks)>1) {
                            $labelModel = $this->_hlp->getLabelTypeInstance($batch->getLabelType());
                            $data['_ATTACHMENTS'][] = $labelModel->renderBatchContent($batch);
                        } else {
                            reset($tracks);
                            $labelModel = $this->_hlp->getLabelTypeInstance($batch->getLabelType());
                            $data['_ATTACHMENTS'][] = $labelModel->renderTrackContent(current($tracks));
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore if failed
            }
        }

        $this->inlineTranslation->suspend();
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $order->getBillingAddress();
        }
        $hlp->assignVendorSkus($shipment);
        $data += array(
            'shipment'        => $shipment,
            'order'           => $order,
            'vendor'          => $this,
            'store_name'      => $store->getName(),
            'vendor_name'     => $this->getVendorName(),
            'order_id'        => $order->getIncrementId(),
            'customer_info'   => $this->_hlp->formatCustomerAddress($shippingAddress, 'html', $this),
            'shipping_method' => $shipment->getUdropshipMethodDescription() ? $shipment->getUdropshipMethodDescription() : $this->getShippingMethodName($order->getShippingMethod(), true),
            'shipment_url'    => $this->_url->getUrl('udropship/vendor/', array('_query'=>'filter_order_id_from='.$order->getIncrementId().'&filter_order_id_to='.$order->getIncrementId())),
            'packingslip_url' => $this->_url->getUrl('udropship/vendor/pdf', array('shipment_id'=>$shipment->getId())),
        );

        $template = $this->getEmailTemplate();
        if (!$template) {
            $template = $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_template', $store);
        }
        $identity = $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store);

        $data['_BCC'] = $this->getNewOrderCcEmails();
        if (($emailField = $this->_hlp->getScopeConfig('udropship/vendor/vendor_notification_field', $store))) {
            $email = $this->getData($emailField) ? $this->getData($emailField) : $this->getEmail();
        } else {
            $email = $this->getEmail();
        }

        $hlp->unassignVendorSkus($shipment);

        $this->_transportBuilder->setTemplateIdentifier(
            $template
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            $data
        )->setFrom(
            $identity
        )->addTo(
            $email,
            $this->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }

    public function getBillingFormatedAddress($type='text')
    {
        switch ($type) {
            case 'text_small':
                $textSmall = '';
                if ($this->getBillingCity()) {
                    $textSmall .= $this->getBillingCity().', ';
                }
                if ($this->getBillingCountryId()) {
                    $textSmall .= $this->getBillingCountryId().' ';
                }
                if ($this->getBillingRegionCode()) {
                    $textSmall .= $this->getBillingRegionCode().' ';
                }
                return rtrim($textSmall, ' ,');
            case 'text':
                return $this->getBillingStreet(-1)."\n".$this->getBillingCity().', '.$this->getBillingRegionCode().' '.$this->getBillingZip();
        }
        /* @var \Magento\Customer\Model\Address\Config $addrCfg */
        $addrCfg = $this->_hlp->getObj('\Magento\Customer\Model\Address\Config');
        $format = $addrCfg->getFormatByCode($type);
        if (!$format) {
            return null;
        }
        $renderer = $format->getRenderer();
        if (!$renderer) {
            return null;
        }
        $address = $this->getBillingAddressObj();
        return $renderer->render($address);
    }

    public function getFormatedAddress($type='text')
    {
        switch ($type) {
        case 'text_small':
            $textSmall = '';
            if ($this->getCity()) {
                $textSmall .= $this->getCity().', ';
            }
            if ($this->getCountryId()) {
                $textSmall .= $this->getCountryId().' ';
            }
            if ($this->getRegionCode()) {
                $textSmall .= $this->getRegionCode().' ';
            }
            return rtrim($textSmall, ' ,');
        case 'text':
            return $this->getStreet(-1)."\n".$this->getCity().', '.$this->getRegionCode().' '.$this->getZip();
        }
        /* @var \Magento\Customer\Model\Address\Config $addrCfg */
        $addrCfg = $this->_hlp->getObj('\Magento\Customer\Model\Address\Config');
        $format = $addrCfg->getFormatByCode($type);
        if (!$format) {
            return null;
        }
        $renderer = $format->getRenderer();
        if (!$renderer) {
            return null;
        }
        $address = $this->getAddressObj();
        return $renderer->render($address);
    }

    protected function _getAddressObj($useBilling=false)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $this->_hlp->createObj('\Magento\Customer\Model\Address');
        foreach (array(
             'billing_email',
             'billing_telephone',
             'billing_fax',
             'billing_vendor_attn',
             'billing_city',
             'billing_zip',
             'billing_country_id',
             'billing_region_id',
             'billing_region',
         ) as $key) {
            $aKey = substr($key, 8);
            if (!$useBilling) {
                $key = $aKey;
            }
            $address->setData($aKey, $this->getDataUsingMethod($key));
        }
        foreach (array(
             'billing_street',
         ) as $key) {
            $aKey = substr($key, 8);
            if (!$useBilling) {
                $key = $aKey;
            }
            $address->setData($aKey, $this->getData($key));
        }
        $address->setPostcode($address->getZip());
        $address->setFirstname($this->getVendorName());
        $address->setLastname($address->getVendorAttn());
        return $address;
    }

    public function getBillingAddressObj()
    {
        return $this->getBillingUseShipping() ? $this->_getAddressObj() : $this->_getAddressObj(true);
    }
    public function getAddressObj()
    {
        return $this->_getAddressObj();
    }

    public function getStreet($line=0)
    {
        $street = parent::getData('street');
        if (-1 === $line) {
            return $street;
        } else {
            $arr = is_array($street) ? $street : explode("\n", $street);
            if (0 === $line || $line === null) {
                return $arr;
            } elseif (isset($arr[$line-1])) {
                return $arr[$line-1];
            } else {
                return '';
            }
        }
    }

    public function getStreet1()
    {
        return $this->getStreet(1);
    }

    public function getStreet2()
    {
        return $this->getStreet(2);
    }

    public function getStreet3()
    {
        return $this->getStreet(3);
    }

    public function getStreet4()
    {
        return $this->getStreet(4);
    }

    public function getStreetFull()
    {
        return $this->getData('street');
    }

    public function setStreetFull($street)
    {
        return $this->setStreet($street);
    }

    public function setStreet($street)
    {
        if (is_array($street)) {
            $street = trim(implode("\n", $street));
        }
        $this->setData('street', $street);
        return $this;
    }

    public function getBillingStreet($line=0)
    {
        $street = parent::getData('billing_street');
        if (-1 === $line) {
            return $street;
        } else {
            $arr = is_array($street) ? $street : explode("\n", $street);
            if (0 === $line || $line === null) {
                return $arr;
            } elseif (isset($arr[$line-1])) {
                return $arr[$line-1];
            } else {
                return '';
            }
        }
    }

    public function getBillingStreet1()
    {
        return $this->getBillingStreet(1);
    }

    public function getBillingStreet2()
    {
        return $this->getBillingStreet(2);
    }

    public function getBillingStreet3()
    {
        return $this->getBillingStreet(3);
    }

    public function getBillingStreet4()
    {
        return $this->getBillingStreet(4);
    }

    public function getBillingStreetFull()
    {
        return $this->getData('billing_street');
    }

    public function setBillingStreetFull($street)
    {
        return $this->setBillingStreet($street);
    }

    public function setBillingStreet($street)
    {
        if (is_array($street)) {
            $street = trim(implode("\n", $street));
        }
        $this->setData('billing_street', $street);
        return $this;
    }


    public function getBillingRegionCode()
    {
        if ($this->getBillingRegionId()) {
            if ($this->_hlp->getRegion($this->getBillingRegionId())->getCountryId() == $this->getCountryId()) {
                return $this->_hlp->getRegionCode($this->getBillingRegionId());
            } else {
                return '';
            }
        }
        return $this->getBillingRegion();
    }

    public function getRegionCode()
    {
        if ($this->getRegionId()) {
            if ($this->_hlp->getRegion($this->getRegionId())->getCountryId() == $this->getCountryId()) {
                return $this->_hlp->getRegionCode($this->getRegionId());
            } else {
                return '';
            }
        }
        return $this->getRegion();
    }

    public function getBillingEmail()
    {
        $email = $this->getBillingUseShipping() || !$this->getData('billing_email')
            ? $this->getEmail() : $this->getData('billing_email');
        return $email;
    }

    public function getBillingTelephone()
    {
        $email = $this->getBillingUseShipping() || !$this->getData('billing_telephone')
            ? $this->getTelephone() : $this->getData('billing_telephone');
        return $email;
    }

    public function getBillingFax()
    {
        $email = $this->getBillingUseShipping() || !$this->getData('billing_fax')
            ? $this->getFax() : $this->getData('billing_fax');
        return $email;
    }
    public function getBillingVendorAttn()
    {
        $email = $this->getBillingUseShipping() || !$this->getData('billing_vendor_attn')
            ? $this->getVendorAttn() : $this->getData('billing_vendor_attn');
        return $email;
    }

    public function getBillingAddress()
    {
        $address = $this->getBillingUseShipping()
            ? $this->getFormatedAddress() : $this->getBillingFormatedAddress();
        return $address;
    }

    public function getBillingInfo()
    {
        $info = $this->getVendorName()."\n";
        if ($this->getBillingVendorAttn()) {
            $info .= $this->getBillingVendorAttn()."\n";
        }
        $info .= $this->getBillingAddress();
        return $info;
    }

    protected $_usePdfCarrierCode;
    public function usePdfCarrierCode($code=null)
    {
        $this->_usePdfCarrierCode=$code;
        return $this;
    }
    public function resetPdfCarrierCode()
    {
        return $this->usePdfCarrierCode();
    }

    public function getLabelType()
    {
        if ($this->_hlp->getScopeFlag('udropship_label/general/use_global')) {
            return $this->_hlp->getScopeConfig('udropship_label/label/label_type');
        } else {
            return $this->getData('label_type');
        }
    }

    public function getPdfLabelWidth()
    {
        $cCode = $this->_usePdfCarrierCode ? $this->_usePdfCarrierCode : $this->getCarrierCode();
        switch ($cCode) {
        case 'usps':
            return $this->getData('endicia_pdf_label_width');
        case 'fedex':
        case 'fedexsoap':
            return $this->getData('fedex_pdf_label_width');
        default:
            return $this->getData($cCode.'_pdf_label_width');
        }
    }

    public function getPdfLabelHeight()
    {
        $cCode = $this->_usePdfCarrierCode ? $this->_usePdfCarrierCode : $this->getCarrierCode();
        switch ($cCode) {
        case 'usps':
            return $this->getData('endicia_pdf_label_height');
        case 'fedexsoap':
            return $this->getData('fedex_pdf_label_height');
        default:
            return $this->getData($cCode.'_pdf_label_height');
        }
    }

    public function getFileUrl($key)
    {
        if ($this->getData($key)) {
            return $this->_url->getBaseUrl(['_type'=>'media']).$this->getData($key);
        }
        return false;
    }

    public function getFilePath($key)
    {
        if ($this->getData($key)) {
            /* @var \Magento\Framework\App\Filesystem\DirectoryList $dirList */
            $dirList = $this->_hlp->getObj('\Magento\Framework\App\Filesystem\DirectoryList');
            return $dirList->getPath('media').'/'.$this->getData($key);
        }
        return false;
    }

    public function getTrackApi($cCode=null)
    {
        if ($this->getPollTracking()=='-') {
            return false;
        }
        if ($this->getPollTracking()!='') {
            $cCode = $this->getPollTracking();
        } elseif (is_null($cCode)) {
            $cCode = $this->getCarrierCode();
        }
        $trackConfig = $this->_hlp->config()->getTrackApi($cCode);
        if (!$trackConfig || @$trackConfig['disabled'] || !@$trackConfig['model']) {
            return false;
        }
        return $this->_hlp->getObj((string)$trackConfig['model']);
    }

    public function getStockcheckCallback($method=null)
    {
        if (is_null($method)) {
            $method = $this->getStockcheckMethod();
        }
        if (!$method) {
            return false;
        }
        $config = $this->_hlp->config()->getStockcheckMethod($method);
        if (!$config || @$config['disabled']) {
            return false;
        }
        $cb = explode('::', (string)$config['callback']);
        $cb[0] = $this->_hlp->getObj($cb[0]);
        if (empty($cb[0]) || empty($cb[1]) || !is_callable($cb)) {
            throw new \Exception(__('Invalid stock check callback: %1', (string)$config['callback']));
        }
        return $cb;
    }
    
    public function getStatementPoType()
    {
        $poType = $this->getData('statement_po_type');
        if ($poType == '999') {
            $poType = $this->_hlp->getScopeConfig('udropship/statement/statement_po_type');
        }
        return !empty($poType) && ($poType != 'po' || $this->_hlp->isUdpoActive()) ? $poType : 'shipment';
    }

    public function getStatementPoStatus()
    {
        $poStatus = $this->getData('statement_po_status');
        if (in_array('999', $poStatus) || empty($poStatus)) {
            if ($this->getStatementPoType()=='po' && $this->_hlp->isUdpoActive()) {
                $poStatus = $this->_hlp->getScopeConfig('udropship/statement/statement_po_status');
            } else {
                $poStatus = $this->_hlp->getScopeConfig('udropship/statement/statement_shipment_status');
            }
            if (!is_array($poStatus)) {
                $poStatus = explode(',', $poStatus);
            }
        }
        return $poStatus;
    }

    public function getStatementDiscountInPayout()
    {
        $ssInPayout = $this->getData('statement_discount_in_payout');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/statement_discount_in_payout');
        }
        return $ssInPayout;
    }

    public function getStatementTaxInPayout()
    {
        $ssInPayout = $this->getData('statement_tax_in_payout');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/statement_tax_in_payout');
        }
        return $ssInPayout;
    }

    public function getStatementShippingInPayout()
    {
        $ssInPayout = $this->getData('statement_shipping_in_payout');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/statement_shipping_in_payout');
        }
        return $ssInPayout;
    }

    public function getIsShippingTaxInShipping()
    {
        $ssInPayout = $this->getData('shipping_tax_in_shipping');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/shipping_tax_in_shipping');
        }
        return $ssInPayout;
    }

    public function getStatementSubtotalBase()
    {
        $ssInPayout = $this->getData('statement_subtotal_base');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/statement_subtotal_base');
        }
        return $ssInPayout;
    }

    public function getApplyCommissionOnTax()
    {
        $ssInPayout = $this->getData('apply_commission_on_tax');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/apply_commission_on_tax');
        }
        return $ssInPayout;
    }
    public function getApplyCommissionOnShipping()
    {
        $ssInPayout = $this->getData('apply_commission_on_shipping');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/apply_commission_on_shipping');
        }
        return $ssInPayout;
    }

    public function getApplyCommissionOnDiscount()
    {
        $ssInPayout = $this->getData('apply_commission_on_discount');
        if ('999' == $ssInPayout) {
            $ssInPayout = $this->_hlp->getScopeConfig('udropship/statement/apply_commission_on_discount');
        }
        return $ssInPayout;
    }

    public function getPayoutPoStatus()
    {
        return $this->getData('payout_po_status_type') == 'payout'
            ? $this->getData('payout_po_status')
            : $this->getStatementPoStatus();
    }

    public function beforeSave()
    {
        parent::beforeSave();

        if (!$this->getData('status')) {
            $this->setData('status', 'I');
        }

        if ($this->hasData('url_key') && !$this->getData('url_key')) {
            $this->unsetData('url_key');
        } elseif ($this->getData('url_key')) {
            $data = $this->getData('url_key');
            $collection = $this->getCollection()->addFieldToFilter('url_key', $data);
            if ($this->getId()) { 
                $collection->addFieldToFilter('vendor_id', array('neq'=>$this->getId()));
            }
            if ($collection->count()) {
                throw new \Exception(__('This URL Key is already used for different vendor (%1). Please choose another.', htmlspecialchars($data)));
            }
            if ($this->_hlp->isUrlKeyReserved($data)) {
                throw new \Exception(__('This URL Key is reserved. Please choose another.'));
            }
        }

        //if ($this->getPassword()) {
            $collection = $this->getCollection()
                ->addFieldToFilter('vendor_id', array('neq'=>$this->getId()))
                ->addFieldToFilter('email', $this->getEmail());
            $dup = false;
            foreach ($collection as $dup) {
                if ($this->_hlp->getScopeConfig('udropship/vendor/unique_email')) {
                    throw new \Exception(__('A vendor with supplied email already exists.'));
                }
                if ($this->_encryptor->validateHash($this->getPassword(), $dup->getPasswordHash())) {
                    throw new \Exception(__('A vendor with supplied email and password already exists.'));
                }
            }
            if ($this->_hlp->getScopeConfig('udropship/vendor/unique_vendor_name')) {
                $collection = $this->getCollection()
                    ->addFieldToFilter('vendor_id', array('neq'=>$this->getId()))
                    ->addFieldToFilter('vendor_name', $this->getVendorName());
                $dup = false;
                foreach ($collection as $dup) {
                    throw new \Exception(__('A vendor with supplied name already exists.'));
                }
            }
        //}

        $handlingConfig = $this->getData('handling_config');
        if (is_array($handlingConfig) && !empty($handlingConfig)
            && !empty($handlingConfig['limit']) && is_array($handlingConfig['limit'])
        ) {
            reset($handlingConfig['limit']);
            $firstTitleKey = key($handlingConfig['limit']);
            if (!is_numeric($firstTitleKey)) {
                $newHandlingConfig = array();
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    array('locale' => $this->_localeResolver->getLocale())
                );
                foreach ($handlingConfig['limit'] as $_k => $_t) {
                    if ( ($_limit = $filter->filter($handlingConfig['limit'][$_k]))
                        && false !== ($_value = $filter->filter($handlingConfig['value'][$_k]))
                    ) {
                        $_limit = is_numeric($_limit) ? $_limit : '*';
                        $_sk    = is_numeric($_limit) ? $_limit : '9999999999';
                        $_sk    = 'str'.str_pad((string)$_sk, 20, '1', STR_PAD_LEFT);
                        $newHandlingConfig[$_sk] = array(
                            'limit' => $_limit,
                            'value' => $_value,
                        );
                    }
                }
                ksort($newHandlingConfig);
                $newHandlingConfig = array_values($newHandlingConfig);
                $this->setData('handling_config', array_values($newHandlingConfig));
            }
        }

        $callEndiciaChangePass = true;
        foreach (array('endicia_requester_id', 'endicia_account_id', 'endicia_pass_phrase') as $eKey) {
            if (!$this->getData($eKey)) {
                $callEndiciaChangePass = false;
                break;
            }
        }
        $eNewPh = $this->getData('endicia_new_pass_phrase');
        $eNewPhC = $this->getData('endicia_new_pass_phrase_confirm');
        $callEndiciaChangePass = $callEndiciaChangePass && $eNewPh;
        if ($callEndiciaChangePass) {
            if ((string)$eNewPh!=(string)$eNewPhC) {
                throw new \Exception('"Endicia New Pass Phrase" should match "Endicia Confirm New Pass Phrase"');
            }
            $this->_hlp->getLabelCarrierInstance('usps')->setVendor($this)->changePassPhrase($eNewPh);
            $this->setData('endicia_pass_phrase', $eNewPh);
            $this->unsetData('endicia_new_pass_phrase');
            $this->unsetData('endicia_new_pass_phrase_confirm');
        }

        $this->_hlp->processCustomVars($this);
    }
    
    public function getHidePackingslipAmount()
    {
        if ($this->getData('hide_packingslip_amount')==-1) {
            return $this->_hlp->getScopeFlag('udropship/vendor/hide_packingslip_amount');
        } else {
            return $this->getData('hide_packingslip_amount');
        }
    }

    public function getHideUdpoPdfShippingAmount()
    {
        if ($this->getData('hide_udpo_pdf_shipping_amount')==-1) {
            return $this->_hlp->getScopeFlag('udropship/vendor/hide_udpo_pdf_shipping_amount');
        } else {
            return $this->getData('hide_udpo_pdf_shipping_amount');
        }
    }

    public function getShowManualUdpoPdfShippingAmount()
    {
        if ($this->getData('show_manual_udpo_pdf_shipping_amount')==-1) {
            return $this->_hlp->getScopeFlag('udropship/vendor/show_manual_udpo_pdf_shipping_amount');
        } else {
            return $this->getData('show_manual_udpo_pdf_shipping_amount');
        }
    }

    public function hasImageUpload($flag=null)
    {
        $oldFlag = $this->_hasImageUpload;
        if ($flag!==null) {
            $this->_hasImageUpload = $flag;
        }
        return $oldFlag;
    }
    protected $_hasImageUpload=false;
    public function afterSave()
    {
        parent::afterSave();

        if (!empty($_FILES)) {
            /** @var \Magento\Framework\App\Filesystem\DirectoryList $dirList */
            $dirList = $this->_hlp->getObj('\Magento\Framework\App\Filesystem\DirectoryList');
            $baseDir = $dirList->getPath('media');
            $vendorDir = 'vendor'.DIRECTORY_SEPARATOR.$this->getId();
            $vendorAbsDir = $baseDir.DIRECTORY_SEPARATOR.$vendorDir;
            /* @var \Magento\Framework\Filesystem\Directory\Write $dirWrite */
            $dirWrite = $this->_hlp->createObj('\Magento\Framework\Filesystem\Directory\WriteFactory')->create($baseDir);
            $dirWrite->create($vendorDir);
            $changedFields = array();
            foreach ($_FILES as $k=>$img) {
                if (empty($img['tmp_name']) || empty($img['name']) || empty($img['type'])) {
                    continue;
                }
                if (!@move_uploaded_file($img['tmp_name'], $vendorAbsDir.DIRECTORY_SEPARATOR.$img['name'])) {
                    throw new \Exception('Error while uploading file: '.$img['name']);
                }
                $changedFields[] = $k;
                $this->setData($k, 'vendor/'.$this->getId().'/'.$img['name']);
            }
            if (!empty($changedFields)) {
                $this->_hasImageUpload = true;
                $changedFields[] = 'custom_vars_combined';
                $this->_hlp->processCustomVars($this);
                /* @var \Unirgy\Dropship\Model\ResourceModel\Helper $rHlp */
                $rHlp = $this->_hlp->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
                $rHlp->updateModelFields($this, $changedFields);
            }
        }
    }

    public function afterCommitCallback()
    {
        if (!$this->getSkipUdropshipVendorIndexer()) {
            /* @var \Magento\Framework\Indexer\IndexerRegistry $indexer */
            $indexerRegistry = $this->_hlp->getObj('\Magento\Framework\Indexer\IndexerRegistry');
            /* @var \Magento\Indexer\Model\Config $indexerConfig */
            $indexerConfig = $this->_hlp->getObj('\Magento\Indexer\Model\Config');
            $indexerId = \Unirgy\Dropship\Model\Indexer\VendorProductAssoc\Processor::INDEXER_ID;
            if ($indexerConfig->getIndexer($indexerId)) {
                $indexer = $indexerRegistry->get($indexerId);
                if ($indexer && !$indexer->isScheduled()) {
                    $indexer->reindexRow($this->getId());
                }
            }
        }
        parent::afterCommitCallback();
        $this->_hasImageUpload = false;
        return $this;
    }

    public function isCountryMatch($countryId)
    {
        if (trim($countryId)=='') return true;
        $match = true;
        $allowed = $this->getAllowedCountries();
        if (!empty($allowed) && !in_array('*', $allowed) && !in_array($countryId, $allowed)) {
            $match = false;
        }
        return $match;
    }
    public function isZipcodeMatch($zipCode)
    {
    	return $this->_hlp->isZipcodeMatch($zipCode, $this->getLimitZipcode());
    }
    public function isAddressMatch($address)
    {
        $result = true;
        static $transport;
        if ($transport === null) {
            $transport = new DataObject;
        }
        $transport->setAllowed($result);
        if ($address) {
            $this->_eventManager->dispatch('udropship_vendor_is_address_match', array('address' => $address, 'vendor' => $this, 'transport' => $transport));
        }
        return $transport->getAllowed();
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_hlp->loadFilteredCustomData($this, $this->getDirectFields());
        $this->_hlp->getVendor($this);
        $this->unsetData('endicia_new_pass_phrase');
        $this->unsetData('endicia_new_pass_phrase_confirm');
    }

    public function getDirectFields()
    {
        return $this->_getResource()->getDirectFields();
    }

    public function afterLoad()
    {
        parent::afterLoad();
        return $this; // added for chaining
    }

    public function updateData($data)
    {
        $this->addData($data);
        $this->getResource()->updateData($this, $data);
        return $this;
    }

    public function getHandlingFee()
    {
        $handlingConfig = $this->getData('handling_config');
        if (is_array($handlingConfig) && !empty($handlingConfig)
            && ($request = $this->getData('__carrier_rate_request'))
            && $request instanceof \Magento\Quote\Model\Quote\Address\RateRequest
            && $this->getData('use_handling_fee') == Source::HANDLING_ADVANCED
        ) {
            $ruleValue = null;
            switch ($this->getData('handling_rule')) {
                case 'price':
                    $ruleValue = $request->getData('package_value');
                    break;
                case 'cost':
                    $ruleValue = $request->getData('package_cost');
                    break;
                case 'qty':
                    $ruleValue = $request->getData('package_qty');
                    break;
                case 'line':
                    $ruleValue = $request->getData('package_lines');
                    break;
                case 'weight':
                    $ruleValue = $request->getData('package_weight');
                    break;
            }
            if (!is_null($ruleValue)) {
                foreach ($handlingConfig as $hc) {
                    if (!isset($hc['limit']) || !isset($hc['value'])) continue;
                    if (is_numeric($hc['limit']) && $ruleValue<=$hc['limit']
                        || !is_numeric($hc['limit'])
                    ) {
                        $handlingFee = $hc['value'];
                        break;
                    }
                }
                if (isset($handlingFee)) {
                    return $handlingFee;
                }
            }
        }
        return $this->getData('handling_fee');
    }

    public function getAllowShippingExtraCharge()
    {
        return $this->_hlp->getScopeConfig('udropship/customer/allow_shipping_extra_charge')
            && $this->getData('allow_shipping_extra_charge');
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

    public function getShowProductsMenuItem()
    {
        $show = $this->_hlp->getScopeFlag('udropship/microsite/show_products_menu_item');
        if (-1!=$this->getData('show_products_menu_item')) {
            $show = $this->getData('show_products_menu_item');
        }
        if ($this->_hlp->isEE()) {
            $show = 0;
        }
        return $show;
    }

    public function getVendorLandingPage()
    {
        if (!$this->_hlp->isModuleActive('Unirgy_DropshipMicrositePro')) return false;
        $pageId = $this->_hlp->getScopeConfig('web/default/umicrosite_default_landingpage');
        if (-1!=$this->getData('cms_landing_page') && $this->getData('cms_landing_page')) {
            $pageId = $this->getData('cms_landing_page');
        }
        return $pageId;
    }

    public function getAllowTiershipModify()
    {
        return $this->_hlp->isModuleActive('Unirgy_DropshipTierShipping') && $this->_hlp->getScopeConfig('carriers/udtiership/allow_vendor_modify');
    }

}
