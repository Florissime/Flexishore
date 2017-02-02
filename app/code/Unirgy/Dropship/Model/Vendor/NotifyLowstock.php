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

namespace Unirgy\Dropship\Model\Vendor;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Magento\ProductAlert\Helper\Data as ProductAlertHelperData;
use \Magento\Store\Model\StoreManagerInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Model\ResourceModel\Vendor\Collection;
use \Unirgy\Dropship\Model\ResourceModel\Vendor\NotifyLowstock\Collection as NotifylowstockCollection;

class NotifyLowstock extends AbstractModel
{
    /**
     * @var Collection
     */
    protected $_vendorCollection;

    /**
     * @var NotifylowstockCollection
     */
    protected $_notifylowstockCollection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductAlertHelperData
     */
    protected $_productAlertHelperData;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Collection $vendorCollection,
        NotifylowstockCollection $notifylowstockCollection,
        ScopeConfigInterface $scopeConfig,
        HelperData $helper,
        StoreManagerInterface $storeManager,
        ProductAlertHelperData $productAlertHelperData,
        \Magento\Framework\UrlInterface $url,
        Context $context,
        Registry $registry, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_vendorCollection = $vendorCollection;
        $this->_notifylowstockCollection = $notifylowstockCollection;
        $this->scopeConfig = $scopeConfig;
        $this->_hlp = $helper;
        $this->_storeManager = $storeManager;
        $this->_productAlertHelperData = $productAlertHelperData;
        $this->_url = $url;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\ResourceModel\Vendor\NotifyLowstock');
        parent::_construct();
    }

    public function vendorNotifyLowstock()
    {
        $vendors = $this->_vendorCollection->addFieldToFilter('notify_lowstock',1);
        $hasEmail = false;
        foreach ($vendors as $vendor) {
            $lsCollection = $this->_notifylowstockCollection->initLowstockSelect($vendor);
            if ($lsCollection->count()>0) {
                $vsAttr = $this->_hlp->getScopeConfig('udropship/vendor/vendor_sku_attribute');
                if (!$this->_hlp->isUdmultiAvailable()) {
                    if ($vsAttr && $vsAttr!='sku' && $this->_hlp->checkProductAttribute($vsAttr)) {
                        foreach ($lsCollection as $prod) {
                            $prod->setVendorSku($prod->getData($vsAttr));
                        }
                    }
                }
                $this->sendLowstockNotificationEmail($lsCollection, $vendor);
                $lsCollection->markLowstockNotified();
                $hasEmail = true;
            }
        }
        return $this;
    }
    public function vendorCleanLowstock()
    {
        $this->getResource()->vendorCleanLowstock();
        return $this;
    }
    
    public function sendLowstockNotificationEmail($lsCollection, $vendor)
    {
        $hlp = $this->_hlp;
        $store = $this->_hlp->getDefaultStoreView();

        $this->inlineTranslation->suspend();

        $data = array(
            'vendor'      => $vendor,
            'store_name'  => $store->getName(),
            'vendor_name' => $vendor->getVendorName(),
            'stock_url'   => $this->_url->getUrl('udropship/vendor/product'),
        );
        
        $data['notification_grid'] = $this->_productAlertHelperData->createBlock('Magento\Framework\View\Element\Template')
            ->setTemplate('Unirgy_Dropship::unirgy/dropship/email/vendor/notification/stockItems.phtml')
            ->setStockItems($lsCollection)
            ->toHtml();

        $template = $this->_hlp->getScopeConfig('udropship/vendor/notify_lowstock_email_template', $store);
        $identity = $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store);

        if (($emailField = $this->_hlp->getScopeConfig('udropship/vendor/vendor_notification_field', $store))) {
            $email = $vendor->getData($emailField) ? $vendor->getData($emailField) : $vendor->getEmail();
        } else {
            $email = $vendor->getEmail();
        }

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
            $vendor->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }
}