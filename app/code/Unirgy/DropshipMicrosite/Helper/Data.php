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

namespace Unirgy\DropshipMicrosite\Helper;

use Magento\Backend\Model\Url;
use Magento\Backend\Model\UrlFactory;
use Magento\Catalog\Model\Product;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Vendor;

class Data extends AbstractHelper
{
    /**
     * @var Url
     */
    protected $_modelUrl;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var UrlFactory
     */
    protected $_backendUrl;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\Url $modelUrl,
        \Unirgy\Dropship\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Url $backendUrl
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_modelUrl = $modelUrl;
        $this->_hlp = $helperData;
        $this->_storeManager = $storeManager;
        $this->_backendUrl = $backendUrl;

        parent::__construct($context);
    }

    public function getLandingPageTitle($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $title = $this->scopeConfig->getValue('udropship/microsite/landing_page_title', ScopeInterface::SCOPE_STORE);
        if ($vendor->getData('landing_page_title')) {
            $title = $vendor->getData('landing_page_title');
        }
        $title = str_replace('[vendor_name]', $vendor->getVendorName(), $title);
        return !empty($title) ? $title : $vendor->getVendorName();
    }
    public function getLandingPageKeywords($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $keywords = $this->scopeConfig->getValue('udropship/microsite/landing_meta_keywords', ScopeInterface::SCOPE_STORE);
        if ($vendor->getData('landing_meta_keywords')) {
            $keywords = $vendor->getData('landing_meta_keywords');
        }
        $keywords = str_replace('[vendor_name]', $vendor->getVendorName(), $keywords);
        return !empty($keywords) ? $keywords : $vendor->getVendorName();
    }
    public function getLandingPageDescription($vendor=null)
    {
        if ($vendor==null) {
            if (!$this->getCurrentVendor()) return '';
            $vendor = $this->getCurrentVendor();
        }
        $description = $this->scopeConfig->getValue('udropship/microsite/landing_meta_description', ScopeInterface::SCOPE_STORE);
        if ($vendor->getData('landing_meta_description')) {
            $description = $vendor->getData('landing_meta_description');
        }
        $description = str_replace('[vendor_name]', $vendor->getVendorName(), $description);
        return !empty($description) ? $description : $vendor->getVendorName();
    }

    public function isCurrentVendorFromProduct()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->isCurrentVendorFromProduct();
    }
    public function resetCurrentVendor()
    {
        $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->resetCurrentVendor();
        return $this;
    }

    public function checkPermission($action, $vendor=null)
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->checkPermission($action, $vendor);
    }

    public function isAllowedAction($action, $vendor=null)
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->isAllowedAction($action, $vendor);
    }

    public function getCurrentVendor()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getCurrentVendor();
    }

    public function getUrlFrontendVendor($url)
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getUrlFrontendVendor($url);
    }
    public function getFrontendVendor()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getFrontendVendor();
    }

    public function getAdminhtmlVendor()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getAdminhtmlVendor();
    }

    public function getManageProductsUrl()
    {
        $params = [];
        if ($this->_backendUrl->useSecretKey()) {
            $params[Url::SECRET_KEY_PARAM_NAME] = $this->_backendUrl->getSecretKey();
        }
        return $this->_backendUrl->getUrl('catalog/product', $params);
    }

    public function getCurrentVendorBaseUrl()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getVendorBaseUrl();
    }

    protected $_updateStoreBaseUrl;
    public function setCurUpdateStoreBaseUrl($ubu)
    {
        $oldUbu = $this->_updateStoreBaseUrl;
        $this->_updateStoreBaseUrl = $ubu;
        return $oldUbu;
    }
    public function getCurUpdateStoreBaseUrl($vendor=null)
    {
        $ubu = $this->scopeConfig->getValue('udropship/microsite/update_store_base_url', ScopeInterface::SCOPE_STORE);
        if ($this->_updateStoreBaseUrl!==null) {
            $ubu = $this->_updateStoreBaseUrl;
        }
        if ($vendor!==null && ($v=$this->_hlp->getVendor($vendor)) && $v->getId() && $v->getUpdateStoreBaseUrl()!=-1) {
            $ubu = $v->getUpdateStoreBaseUrl();
        }
        return $ubu;
    }
    public function getUpdateStoreBaseUrl($vendor=null)
    {
        $ubu = $this->scopeConfig->getValue('udropship/microsite/update_store_base_url', ScopeInterface::SCOPE_STORE);
        if ($vendor!==null && ($v=$this->_hlp->getVendor($vendor)) && $v->getId() && $v->getUpdateStoreBaseUrl()!=-1) {
            $ubu = $v->getUpdateStoreBaseUrl();
        }
        return $ubu;
    }

    protected $_subdomainLevel;
    public function setCurSubdomainLevel($sl)
    {
        $oldSl = $this->_subdomainLevel;
        $this->_subdomainLevel = $sl;
        return $oldSl;
    }
    public function getCurSubdomainLevel($vendor=null)
    {
        $sl = $this->scopeConfig->getValue('udropship/microsite/subdomain_level', ScopeInterface::SCOPE_STORE);
        if ($this->_subdomainLevel!==null) {
            $sl = $this->_subdomainLevel;
        }
        if ($vendor!==null && ($v=$this->_hlp->getVendor($vendor)) && $v->getId() && $v->getSubdomainLevel()) {
            $sl = $v->getSubdomainLevel();
        }
        return $sl;
    }
    public function getSubdomainLevel($vendor=null)
    {
        $sl = $this->scopeConfig->getValue('udropship/microsite/subdomain_level', ScopeInterface::SCOPE_STORE);
        if ($vendor!==null && ($v=$this->_hlp->getVendor($vendor)) && $v->getId() && $v->getSubdomainLevel()) {
            $sl = $v->getSubdomainLevel();
        }
        return $sl;
    }

    public function getVendorBaseUrl($vendor=null)
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getVendorBaseUrl($vendor);
    }

    public function withOrigBaseUrl($url, $prefix='')
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->withOrigBaseUrl($url, $prefix);
    }

    public function updateStoreBaseUrl()
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->updateStoreBaseUrl();
    }

    /**
    * Get URL specific for vendor
    *
    * @param boolean|integer|Vendor $vendor
    * @param string|Product $orig original product or URL to be converted to vendor specific
    */
    public function getVendorUrl($vendor, $origUrl=null)
    {
        return $this->_hlp->getObj('\Unirgy\DropshipMicrosite\Helper\ProtectedCode')->getVendorUrl($vendor, $origUrl);
    }

    public function getProductUrl($product)
    {
        return $this->getVendorUrl($this->_hlp->getVendor($product), $product);
    }

    public function getVendorRegisterUrl()
    {
        return $this->_urlBuilder->getUrl('umicrosite/vendor/register');
    }

    public function sendVendorSignupEmail($registration)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_hlp->getScopeConfig('udropship/microsite/signup_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store_name' => $store->getName(),
                'vendor' => $registration,
            ]
        )->setFrom(
            $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
        )->addTo(
            $registration->getEmail(),
            $registration->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }

    public function sendVendorWelcomeEmail($vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();

        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_hlp->getScopeConfig('udropship/microsite/welcome_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            ]
        )->setFrom(
            $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
        )->addTo(
            $vendor->getEmail(),
            $vendor->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }

    public function getDomainName()
    {
        $level = $this->getSubdomainLevel();
        if (!$level) {
            return '';
        }
        $baseUrl = $this->scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORE);
        $url = parse_url($baseUrl);
        $hostArr = explode('.', $url['host']);
        return join('.', array_slice($hostArr, -($level-1)));
    }

    /**
    * Send new registration to store owner
    *
    * @param Shipment $shipment
    * @param string $comment
    */
    public function sendVendorRegistration($registration)
    {
        $store = $this->_storeManager->getStore($registration->getStoreId());
        $to = $this->_hlp->getScopeConfig('udropship/microsite/registration_receiver', $store);
        $subject = $this->_hlp->getScopeConfig('udropship/microsite/registration_subject', $store);
        $template = $this->_hlp->getScopeConfig('udropship/microsite/registration_template', $store);

        if ($to && $subject && $template) {
            $data = $registration->getData();
            $data['store_name'] = $store->getName();
            $data['registration_url'] = $this->_backendUrl->getUrl('umicrosite/registration/edit', [
                'reg_id' => $registration->getId(),
                'key' => null,
                '_store'    => 0
            ]);
            $data['all_registrations_url'] = $this->_backendUrl->getUrl('umicrosite/registration', [
                'key' => null,
                '_store'    => 0
            ]);

            foreach ($data as $k=>$v) {
                $_v = is_array($v) ? implode(', ', $v) : $v;
                $subject = str_replace('{{'.$k.'}}', $_v, $subject);
                $template = str_replace('{{'.$k.'}}', $_v, $template);
            }

            foreach (explode(',', $to) as $toEmail) {
                /** @var \Magento\Framework\Mail\Message $message */
                $message = $this->_hlp->createObj('Magento\Framework\Mail\Message');
                $message->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_TEXT)
                    ->setFrom($registration->getEmail(), $registration->getVendorName())
                    ->addTo($toEmail, '')
                    ->setSubject($subject)
                    ->setBodyText($template);
                $transport = $this->_hlp->createObj('Magento\Framework\Mail\TransportInterface', ['message' => $message]);
                $transport->sendMessage();
            }
        }

        return $this;
    }

    public function addVendorFilterToSearchQuery($select)
    {
        $vendor = $this->getCurrentVendor();

        if ($this->isCurrentVendorFromProduct()) {
            $vendor = false;
        }

        try {
            $vpAssocTbl = $this->_hlp->rHlp()->getTableName('udropship_vendor_product_assoc');
            if ($vendor) {
                $joinCond = $vpAssocTbl.'.vendor_id='.intval($vendor->getId());
                if (!$this->scopeConfig->isSetFlag('udropship/microsite/front_show_all_products', ScopeInterface::SCOPE_STORE)) {
                    $joinCond .= ' and '.$vpAssocTbl.'.is_attribute=1';
                }
                $select->join(
                    $vpAssocTbl,
                    $vpAssocTbl.'.product_id=search_index.entity_id AND '.$joinCond,
                    ['microsite_vendor'=>'vendor_id']
                );
            }
        } catch (\Exception $e) {
            $skip = [
                __('Joined field with this alias is already declared'),
                __('Invalid alias, already exists in joined attributes'),
                __('Invalid alias, already exists in joint attributes.'),
            ];
            if (!in_array($e->getMessage(), $skip)) {
                throw $e;
            }
        }
        return $this;
    }

    public function addVendorFilterToProductCollection($collection)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $vendor = $this->getCurrentVendor();

        if ($this->isCurrentVendorFromProduct()) {
            $vendor = false;
        }

        try {
            $vpAssocTbl = $this->_hlp->rHlp()->getTableName('udropship_vendor_product_assoc');
            if ($vendor) {
                if (!$collection->getFlag('udropship_vendor_joined')) {
                    $joinCond = '{{table}}.vendor_id='.intval($vendor->getId());
                    if (!$this->scopeConfig->isSetFlag('udropship/microsite/front_show_all_products', ScopeInterface::SCOPE_STORE)) {
                        $joinCond .= ' and {{table}}.is_attribute=1';
                    }
                    $collection->joinTable(
                        $vpAssocTbl, 'product_id=entity_id',
                        ['microsite_vendor'=>'vendor_id'],
                        $joinCond
                    );
                    $collection->setFlag('udropship_vendor_joined',1);
                }
            } else {
                $cond = "{{table}}.vendor_id IS null OR {{table}}.status='A'";
                $session = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session');
                if ($session->isLoggedIn() && $session->getVendor()->getStatus()=='I') {
                    $cond .= " OR {{table}}.vendor_id=".$session->getVendor()->getId();
                }
                $alreadyJoined = false;
                foreach ($collection->getSelect()->getPart(Select::COLUMNS) as $column) {
                    if ($column[2]=='udropship_vendor' || $column[2]=='udropship_status') {
                        $alreadyJoined = true;
                        break;
                    }
                }
                if (!$alreadyJoined) {
                    $collection->joinAttribute('udropship_vendor', 'catalog_product/udropship_vendor', 'entity_id', null, 'left');
                    $collection->joinField('udropship_status', 'udropship_vendor', 'status', 'vendor_id=udropship_vendor', $cond, 'left');
                }
            }
        } catch (\Exception $e) {
            $skip = [
                __('Joined field with this alias is already declared'),
                __('Invalid alias, already exists in joined attributes'),
                __('Invalid alias, already exists in joint attributes.'),
            ];
            if (!in_array($e->getMessage(), $skip)) {
                throw $e;
            }
        }
        return $this;
    }
    protected $_vendorCatIds;
    public function getVendorCategoryIds()
    {
        if (is_null($this->_vendorCatIds)) {
            $this->_vendorCatIds = [];
            if (($v = $this->getCurrentVendor()) && $v->getIsLimitCategories()) {
                $this->_vendorCatIds = explode(',', implode(',', (array)$v->getLimitCategories()));
            }
        }
        return $this->_vendorCatIds;
    }
    public function getVendorEnableCategories()
    {
        $v = $this->getCurrentVendor();
        if ($v && $v->getIsLimitCategories() == 1) {
            return $this->getVendorCategoryIds();
        } else {
            return false;
        }
    }
    public function getVendorDisableCategories()
    {
        $v = $this->getCurrentVendor();
        if ($v && $v->getIsLimitCategories() == 2) {
            return $this->getVendorCategoryIds();
        } else {
            return false;
        }
    }
    public function useVendorCategoriesFilter()
    {
        $isAdmin = $this->_hlp->isAdmin();
        return ($v = $this->getCurrentVendor()) && $v->getIsLimitCategories() && !$isAdmin && !$this->isCurrentVendorFromProduct();
    }

}
