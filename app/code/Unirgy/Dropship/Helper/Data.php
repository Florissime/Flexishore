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

namespace Unirgy\Dropship\Helper {

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\DataObject;
use \Magento\Framework\Data\Collection as DataCollection;
use \Magento\Framework\View\DesignInterface;
use \Magento\Shipping\Model\Carrier\AbstractCarrier;
use Symfony\Component\Config\Definition\Exception\Exception;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Model\Vendor;
use \Magento\Catalog\Model\Product;
use \Magento\Sales\Model\Order\Shipment;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    /**
     * @var DesignInterface
     */
    protected $_viewDesignInterface;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_dirList;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objMng;

    /**
     * @var \Magento\Framework\Module\ModuleList
     */
    protected $_moduleList;

    protected $inlineTranslation;
    protected $_transportBuilder;

    protected $_stockRegistry;
    protected $_stockState;

    protected $scopePool;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Filesystem\DirectoryList $dirList,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\Request\Http $appRequestInterface,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        DesignInterface $viewDesignInterface,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Framework\App\Config\ScopePool $scopePool,
        \Magento\Framework\App\ProductMetadataInterface $appProductMeta
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_objMng = $objectManager;
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->_request = $appRequestInterface;
        $this->_carrierFactory = $carrierFactory;
        $this->_viewDesignInterface = $viewDesignInterface;
        $this->_registry = $registry;
        $this->_dirList = $dirList;
        $this->_moduleList = $moduleList;
        $this->_stockRegistry = $stockRegistry;
        $this->_stockState = $stockState;
        $this->scopePool = $scopePool;
        $this->appProductMeta = $appProductMeta;

        parent::__construct($context);
    }

    /**
     * Vendors cache
     *
     * @var array
     */
    protected $_vendors = array();

    /**
     * Regions cache
     *
     * @var array
     */
    protected $_regions = array();

    /**
     * Writable flag to show true stock status or not
     *
     * @var boolean
     */
    protected $_trueStock = false;

    /**
     * Collection of order shipments for the vendor interface
     *
     * @var mixed
     */
    protected $_vendorShipmentCollection;

    /**
     * Carrier Methods Cache
     *
     * @var array
     */
    protected $_carrierMethods = array();

    protected $_version;

    protected $_localVendorId;

    protected $_isActive;

    public function getVersion()
    {
        if (!$this->_version) {
            $moduleName = 'Unirgy_Dropship';
            $curVer = null;
            if ($this->_moduleList->has($moduleName)) {
                $moduleData = $this->_moduleList->getOne($moduleName);
                if($moduleData){
                    $this->_version = $moduleData['setup_version'];
                }
            }
        }
        return $this->_version;
    }

    public function isActive($store = null)
    {
        $storeId = $this->_storeManager->getStore($store)->getId();
        if (isset($this->_isActive[$storeId])) {
            return $this->_isActive[$storeId];
        }
        if (!extension_loaded('ionCube Loader')) {
            return ($this->_isActive[$storeId] = false);
        }
        if ($this->isModuleActive('Unirgy_SimpleLicense')) {
            try {
                ProtectedCode::validateLicense('Unirgy_Dropship');
            } catch (\Unirgy\SimpleLicense\Exception $e) {
                return ($this->_isActive[$storeId] = false);
            }
        } else {
            $hlpPr = $this->getObj('\Unirgy\Dropship\Helper\ProtectedCode');
            if (!$hlpPr->validateLicense('Unirgy_Dropship')) {
                return ($this->_isActive[$storeId] = false);
            }
        }
        $udropship = $this->getScopeFlag('carriers/udropship/active', $store);
        $udsplit = $this->getScopeFlag('carriers/udsplit/active', $store);
        $forced = $this->getScopeFlag('carriers/udropship/force_active', $store);
        return ($this->_isActive[$storeId] = $udropship || $udsplit || $forced);
    }

    public function isModulesActive($codes)
    {
        if (!is_array($codes)) {
            $codes = explode(',', $codes);
        }
        $true = true;
        foreach ($codes as $code) {
            $true = $true && $this->isModuleActive($code);
        }
        return $true;
    }

    public function isOSPActive()
    {
        return false;
        return $this->isModuleActive('Organic_Internet_SimpleConfigurableProducts');
    }

    public function isModuleActive($code)
    {
        return (bool)$this->_moduleManager->isEnabled($code);
    }

    public function isUdpayoutActive()
    {
        return $this->isModuleActive('Unirgy_DropshipPayout');
    }

    public function isUdsprofileActive()
    {
        return $this->isModuleActive('Unirgy_DropshipShippingProfile');
    }

    public function isUdpoActive()
    {
        return $this->isModuleActive('Unirgy_DropshipPo') && $this->udpoHlp()->isActive();
    }

    protected $_ustockpoHlp;

    /**
     */
    public function ustockpoHlp()
    {
        if ($this->_ustockpoHlp === null) {
            $this->_ustockpoHlp = $this->_objMng->get('\Unirgy\DropshipStockPo\Helper\Data');
        }
        return $this->_ustockpoHlp;
    }

    protected $_udpoHlp;

    /**
     * @return \Unirgy\DropshipPo\Helper\Data
     */
    public function udpoHlp()
    {
        if ($this->_udpoHlp === null) {
            $this->_udpoHlp = $this->_objMng->get('\Unirgy\DropshipPo\Helper\Data');
        }
        return $this->_udpoHlp;
    }

    protected $_udpoHlpPr;

    /**
     * @return \Unirgy\DropshipPo\Helper\ProtectedCode
     */
    public function udpoHlpPr()
    {
        if ($this->_udpoHlpPr === null) {
            $this->_udpoHlpPr = $this->_objMng->get('\Unirgy\DropshipPo\Helper\ProtectedCode');
        }
        return $this->_udpoHlpPr;
    }

    public function isUdsplitActive()
    {
        return $this->isModuleActive('Unirgy_DropshipSplit') && $this->udsplitHlp()->isActive();
    }

    protected $_udsplitHlp;

    public function udsplitHlp()
    {
        if ($this->_udsplitHlp === null) {
            $this->_udsplitHlp = $this->_objMng->get('\Unirgy\DropshipSplit\Helper\Data');
        }
        return $this->_udsplitHlp;
    }

    public function isUdmultiPriceAvailable()
    {
        return $this->isUdmultiAvailable() && $this->isModuleActive('Unirgy_DropshipMultiPrice');
    }

    public function isUdmultiPriceActive()
    {
        return $this->isUdmultiActive() && $this->isModuleActive('Unirgy_DropshipMultiPrice');
    }

    public function isUdmultiActive()
    {
        return $this->isModuleActive('Unirgy_DropshipMulti') && $this->udmultiHlp()->isActive();
    }

    protected $_udmultiHlp;

    public function udmultiHlp()
    {
        if ($this->_udmultiHlp === null) {
            $this->_udmultiHlp = $this->_objMng->get('\Unirgy\DropshipMulti\Helper\Data');
        }
        return $this->_udmultiHlp;
    }

    protected $_udtaxHlp;

    /**
     * @return \Unirgy\DropshipVendorTax\Helper\Data
     */
    public function udtaxHlp()
    {
        if ($this->_udtaxHlp === null) {
            $this->_udtaxHlp = $this->_objMng->get('\Unirgy\DropshipVendorTax\Helper\Data');
        }
        return $this->_udtaxHlp;
    }

    protected $_udsprofileHlp;

    public function udsprofileHlp()
    {
        if ($this->_udsprofileHlp === null) {
            $this->_udsprofileHlp = $this->_objMng->get('\Unirgy\DropshipShippingProfile\Helper\Data');
        }
        return $this->_udsprofileHlp;
    }

    public function isUdmultiAvailable()
    {
        return $this->isUdmultiActive();
    }

    public function hasUdmulti()
    {
        return $this->isUdmultiActive();
    }

    public function isUdpoMpsAvailable($carrierCode)
    {
        return in_array($carrierCode, array('fedex', 'fedexsoap', 'ups', 'usps', 'endicia')) && $this->isModuleActive('Unirgy_DropshipPoMps');
    }

    /**
     * Retrieve local vendor id
     *
     * @param integer $store
     * @return integer
     */
    public function getLocalVendorId($store = null)
    {
        if (is_null($this->_localVendorId)) {
            $this->_localVendorId = $this->getScopeConfig('udropship/vendor/local_vendor', $store);
            // can't proceed if not configured
            if (!$this->_localVendorId) {
                #throw new \Exception('Local vendor is not set, please configure correctly');
                $this->_localVendorId = 0;
            }
        }
        return $this->_localVendorId;
    }

    /**
     * Get vendor object for vendor ID or Name and cache it
     *
     * If argument is product model, get udropship_vendor value
     *
     * @param integer|string|Product $id
     * @return Vendor
     */
    public function getVendor($id)
    {
        if ($id instanceof Vendor) {
            if (empty($this->_vendors[$id->getId()])) {
                $this->_vendors[$id->getId()] = $id;
            }
            return $id;
        }
        if ($id instanceof Product) {
            $id = $this->getProductVendorId($id);
        }
        if (empty($id)) {
            /* @var \Unirgy\Dropship\Model\Vendor $vendor */
            $vendor = $this->createObj('\Unirgy\Dropship\Model\Vendor');
            return $vendor;
        }
        if (empty($this->_vendors[$id])) {
            /* @var \Unirgy\Dropship\Model\Vendor $vendor */
            $vendor = $this->createObj('\Unirgy\Dropship\Model\Vendor');
            if (!is_numeric($id)) {
                $vendor->load($id, 'vendor_name');
                if ($vendor->getId()) {
                    $this->_vendors[$vendor->getId()] = $vendor;
                }
            } else {
                $vendor->load($id);
                if ($vendor->getId()) {
                    $this->_vendors[$vendor->getVendorName()] = $vendor;
                }
            }
            $this->_vendors[$id] = $vendor;
        }
        return $this->_vendors[$id];
    }

    public function getVendorName($id)
    {
        $v = $this->getVendor($id);
        if ($v->getId()) {
            return $v->getVendorName();
        }
        return false;
    }

    public function getVendorDecisionModel()
    {

    }

    public function getVendorForgotPasswordUrl()
    {
        return $this->_getUrl('udropship/vendor/password');
    }

    /**
     * Get shipment status name from shipment object
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getShipmentStatusName($shipment)
    {
        $statuses = $this->src()->setPath('shipment_statuses')->toOptionHash();
        $id = $shipment instanceof Shipment ? $shipment->getUdropshipStatus() : $shipment;
        return isset($statuses[$id]) ? $statuses[$id] : 'Unknown';
    }

    /**
     * Get shipment method name from shipment object
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param boolean $full whether to prefix with carrier name
     * @return string
     */
    public function getShipmentMethodName($shipment, $full = false)
    {
        $vendor = $this->getVendor($shipment->getUdropshipVendor());
        $method = $shipment->getOrder()->getShippingMethod();
        return $vendor->getShippingMethodName($method, $full);
    }

    /**
     * Return vendor ID for a product object
     *
     * @param mixed $product
     * @param boolean $forceReal
     */
    public function getProductVendorId($product, $forceReal = false)
    {
        $storeId = $product->getStoreId();
        $localVendorId = $this->getLocalVendorId($storeId);
        $vendorId = $product->getUdropshipVendor();

        // product doesn't have vendor specified
        if (!$vendorId) {
            return $localVendorId;
        }
        // force real product vendor
        if ($forceReal) {
            return $vendorId;
        }

        // all other cases
        return $vendorId;
    }

    /**
     * Return vendor ID for quote item based on requested qty
     *
     * if $qty===true, always return dropship vendor id
     * if $qty===false, always return local vendor id
     * otherwise return local vendor if enough qty in stock
     *
     * @param Item $item
     * @param integer|boolean $qty
     * @return integer
     * @deprecated since 1.6.0
     */
    public function getQuoteItemVendor($item, $qty = 0)
    {
        $product = $item->getProduct();
        if (!$product || !$product->hasUdropshipVendor()) {
            // if not available, load full product info to get product vendor
            /* @var \Magento\Catalog\Model\Product $product */
            $product = $this->createObj('\Magento\Catalog\Model\Product')->load($item->getProductId());
        }
        $store = $item->getQuote() ? $item->getQuote()->getStore() : $item->getOrder()->getStore();

        $localVendorId = $this->getLocalVendorId($store);
        $vendorId = $product->getUdropshipVendor();
        // product doesn't have vendor specified OR force local vendor
        if (!$vendorId || $qty === false) {
            return $localVendorId;
        }
        // force real vendor
        if ($qty === true) {
            return $vendorId;
        }

        // local stock is available
        if ($this->getObj('\Unirgy\Dropship\Model\Stock\Availability')->getUseLocalStockIfAvailable($store, $vendorId)
            && $this->_stockState->checkQty($product->getId(), $qty)
        ) {
            return $localVendorId;
        }

        // all other cases
        return $vendorId;
    }

    /**
     * Get vendors collection for quote items
     *
     * @deprecated
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\Collection $items
     * @return \Unirgy\Dropship\Model\ResourceModel\Vendor\Collection
     */
    public function collectQuoteItemsVendors($items)
    {
        $productQtys = array();
        foreach ($items as $item) {
            $id = $item->getProductId();
            if (isset($productQtys[$id])) {
                $productQtys[$id] += $item->getQty();
            } else {
                $productQtys[$id] = $item->getQty();
            }
        }
        /* @var \Unirgy\Dropship\Model\ResourceModel\Vendor\Collection $vendors */
        $vendors = $this->createObj('\Unirgy\Dropship\Model\ResourceModel\Vendor\Collection');
        $vendors->addProductFilter(array_keys($productQtys), 1);
        return $vendors;
    }

    /**
     * Mark shipment as complete and shipped
     *
     * @param Shipment $shipment
     */
    public function setShipmentComplete($shipment)
    {
        $this->completeShipment($shipment, true);
        $this->completeUdpoIfShipped($shipment, true);
        $this->completeOrderIfShipped($shipment, true);
        return $this;
    }

    public function sendPasswordResetEmail($email)
    {
        /* @var \Unirgy\Dropship\Model\Vendor $vendor */
        $vendor = $this->createObj('\Unirgy\Dropship\Model\Vendor');
        $vendor->load($email, 'email');
        if (!$vendor->getId()) {
            return false;
        }
        $vendor->setRandomHash(sha1(rand()))->save();

        $store = $this->_storeManager->getStore();
        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->getScopeConfig('udropship/vendor/vendor_password_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store_name' => $store->getName(),
                'vendor_name' => $vendor->getVendorName(),
                'url' => $this->_getUrl('udropship/vendor/password', array(
                    'confirm' => $vendor->getRandomHash(),
                ))
            ]
        )->setFrom(
            $this->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
        )->addTo(
            $email,
            $vendor->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return true;
    }

    /**
     * Send notification to vendor about new order
     *
     * @param Shipment $shipment
     */
    public function sendVendorNotification($shipment)
    {
        $vendor = $this->getVendor($shipment->getUdropshipVendor());
        $method = $vendor->getNewOrderNotifications();

        if (!$method || $method == '0') {
            return $this;
        }

        $data = compact('vendor', 'shipment', 'method');
        if ($method == '1') {
            $vendor->sendOrderNotificationEmail($shipment);
        } else {
            $config = $this->config()->getNotificationMethod($method);
            if ($config) {
                $cb = explode('::', (string)$config['callback']);
                $obj = $this->getObj($cb[0]);
                $method = $cb[1];
                $obj->$method($data);
            }
        }
        $this->_eventManager->dispatch('udropship_send_vendor_notification', $data);

        return $this;
    }

    public function sendShipmentCommentNotificationEmail($shipment, $comment)
    {
        $order = $shipment->getOrder();
        $store = $order->getStore();

        $vendor = $this->getVendor($shipment->getUdropshipVendor());

        $hlp = $this;
        $data = array();

        $this->inlineTranslation->suspend();

        $data += array(
            'shipment' => $shipment,
            'order' => $order,
            'vendor' => $vendor,
            'comment' => $comment,
            'store_name' => $store->getName(),
            'vendor_name' => $vendor->getVendorName(),
            'shipment_id' => $shipment->getIncrementId(),
            'shipment_status' => $this->getShipmentStatusName($shipment),
            'order_id' => $order->getIncrementId(),
            'shipment_url' => $this->_getUrl('udropship/vendor/', array('_query' => 'filter_order_id_from=' . $order->getIncrementId() . '&filter_order_id_to=' . $order->getIncrementId())),
            'packingslip_url' => $this->_getUrl('udropship/vendor/pdf', array('shipment_id' => $shipment->getId())),
        );

        if ($this->isUdpoActive() && ($po = $this->udpoHlp()->getShipmentPo($shipment))) {
            $data['po'] = $po;
            $data['po_id'] = $po->getIncrementId();
            $data['po_url'] = $this->_getUrl('udpo/vendor/', array('_query' => 'filter_po_id_from=' . $po->getIncrementId() . '&filter_po_id_to=' . $po->getIncrementId()));
        }

        $template = $this->getScopeConfig('udropship/vendor/shipment_comment_vendor_email_template', $store);
        $identity = $this->getScopeConfig('udropship/vendor/vendor_email_identity', $store);

        $data['_BCC'] = $vendor->getNewOrderCcEmails();
        if (($emailField = $this->getScopeConfig('udropship/vendor/vendor_notification_field', $store))) {
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

    /**
     * Send vendor comment to store owner
     *
     * @param Shipment $shipment
     * @param string $comment
     */
    public function sendVendorComment($shipment, $comment)
    {
        $order = $shipment->getOrder();
        $store = $order->getStore();
        $to = $this->getScopeConfig('udropship/admin/vendor_comments_receiver', $store);
        $subject = $this->getScopeConfig('udropship/admin/vendor_comments_subject', $store);
        $template = $this->getScopeConfig('udropship/admin/vendor_comments_template', $store);
        $vendor = $this->getVendor($shipment->getUdropshipVendor());
        /* @var \Magento\Backend\Model\Url $ahlp */
        $ahlp = $this->createObj('\Magento\Backend\Model\Url');

        if ($subject && $template && $vendor->getId()) {
            $toEmail = $this->getScopeConfig('trans_email/ident_' . $to . '/email', $store);
            $toName = $this->getScopeConfig('trans_email/ident_' . $to . '/name', $store);
            $data = array(
                'vendor_name' => $vendor->getVendorName(),
                'order_id' => $order->getIncrementId(),
                'shipment_id' => $shipment->getIncrementId(),
                'vendor_url' => $ahlp->getUrl('udropship/vendor/edit', array(
                    'id' => $vendor->getId(),
                    '_store' => 0
                )),
                'order_url' => $ahlp->getUrl('sales/order/view', array(
                    'order_id' => $order->getId(),
                    '_store' => 0
                )),
                'shipment_url' => $ahlp->getUrl('sales/order_shipment/view', array(
                    'shipment_id' => $shipment->getId(),
                    'order_id' => $order->getId(),
                    '_store' => 0
                )),
                'comment' => $comment,
            );
            if ($this->isUdpoActive() && ($po = $this->udpoHlp()->getShipmentPo($shipment))) {
                $data['po_id'] = $po->getIncrementId();
                $data['po_url'] = $ahlp->getUrl('udpo/order_po/view', array(
                    'udpo_id' => $po->getId(),
                    'order_id' => $order->getId(),
                    '_store' => 0
                ));
                $template = preg_replace('/{{isPoAvailable}}(.*?){{\/isPoAvailable}}/s', '\1', $template);
            } else {
                $template = preg_replace('/{{isPoAvailable}}.*?{{\/isPoAvailable}}/s', '', $template);
            }
            foreach ($data as $k => $v) {
                $subject = str_replace('{{' . $k . '}}', $v, $subject);
                $template = str_replace('{{' . $k . '}}', $v, $template);
            }

            /** @var \Magento\Framework\Mail\Message $message */
            $message = $this->createObj('Magento\Framework\Mail\Message');
            $message->setMessageType(\Magento\Framework\Mail\MessageInterface::TYPE_TEXT)
                ->setFrom($vendor->getEmail(), $vendor->getVendorName())
                ->addTo($toEmail, $toName)
                ->setSubject($subject)
                ->setBodyText($template);
            $transport = $this->createObj('Magento\Framework\Mail\TransportInterface', ['message' => $message]);
            $transport->sendMessage();
        }

        $this->addShipmentComment(
            $shipment,
            __($vendor->getVendorName() . ': ' . $comment)
        );
        $shipment->getCommentsCollection()->save();

        return $this;
    }

    /**
     * Get file name of label image for shipment tracking
     *
     * @todo make flexible enough for EPL
     * @param Track $track
     * @return string
     */
    public function getTrackLabelFileName($track)
    {
        $shipment = $track->getShipment();
        return $this->_dirList->getPath('var') . ('label') . '/' . $track->getNumber() . '.png';
    }

    /**
     * In case customer object is missing in order object, retrieve
     *
     * @param Order $order
     * @return Customer
     */
    public function getOrderCustomer($order)
    {
        if (!$order->hasCustomer()) {
            /* @var \Magento\Customer\Model\Customer $customer */
            $customer = $this->createObj('\Magento\Customer\Model\Customer');
            $order->setCustomer($customer->load($order->getCustomerId()));
        }
        return $order->getCustomer();
    }

    /**
     * Get collection of order shipments for vendor interface
     *
     */
    public function getUsedMethodsByPoCollection($collection)
    {
        $allIds = $collection->getAllIds();
        $res = $this->rHlp();
        $read = $res->getConnection();
        if ($collection instanceof \Unirgy\DropshipPo\Model\ResourceModel\Po\Collection) {
            return $read->fetchCol(
                $read->select()->distinct(true)
                    ->from($res->getTableName('udropship_po'), array('if(is_virtual, "VIRTUAL_PO", udropship_method)'))
                    ->where('entity_id in (?)', $allIds)
            );
        } else {
            return $read->fetchCol(
                $read->select()->distinct(true)
                    ->from($res->getTableName('sales_shipment'), array('udropship_method'))
                    ->where('entity_id in (?)', $allIds)
            );
        }
    }

    public function getVendorShipmentCollection()
    {
        if (!$this->_vendorShipmentCollection) {
            /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate */
            $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
            $datetimeFormatInt = \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT;
            $dateFormat = $localeDate->getDateFormat(\IntlDateFormatter::SHORT);
            $vendorId = $this->session()->getVendorId();
            $vendor = $this->getVendor($vendorId);
            /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $collection */
            $collection = $this->createObj('\Magento\Sales\Model\Order\Shipment')->getCollection();
            $orderTableQted = $collection->getResource()->getConnection()->quoteIdentifier('sales_order');

            $collection->join('sales_order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));

            $collection->addAttributeToFilter('main_table.udropship_vendor', $vendorId);


            $r = $this->_request;
            if (($v = $r->getParam('filter_order_id_from'))) {
                $collection->addAttributeToFilter('sales_order.increment_id', array('gteq' => $v));
            }
            if (($v = $r->getParam('filter_order_id_to'))) {
                $collection->addAttributeToFilter('sales_order.increment_id', array('lteq' => $v));
            }

            if (($v = $r->getParam('filter_order_date_from'))) {
                $_filterDate = $this->dateLocaleToInternal($v, $dateFormat, true);
                $_filterDate = $localeDate->date($_filterDate, null, false);
                $_filterDate = datefmt_format_object($_filterDate, $datetimeFormatInt);
                $collection->addAttributeToFilter('sales_order.created_at', ['gteq' => $_filterDate]);
            }
            if (($v = $r->getParam('filter_order_date_to'))) {
                $_filterDate = $this->dateLocaleToInternal($v, $dateFormat, true);
                $_filterDate = $localeDate->date($_filterDate, null, false);
                $_filterDate->add(new \DateInterval('P1D'));
                $_filterDate->sub(new \DateInterval('PT1S'));
                $_filterDate = datefmt_format_object($_filterDate, $datetimeFormatInt);
                $collection->addAttributeToFilter('sales_order.created_at', ['lteq' => $_filterDate]);

            }

            if (($v = $r->getParam('filter_shipment_date_from'))) {
                $_filterDate = $this->dateLocaleToInternal($v, $dateFormat, true);
                $_filterDate = $localeDate->date($_filterDate, null, false);
                $_filterDate = datefmt_format_object($_filterDate, $datetimeFormatInt);
                $collection->addAttributeToFilter('main_table.created_at', ['gteq' => $_filterDate]);
            }
            if (($v = $r->getParam('filter_shipment_date_to'))) {
                $_filterDate = $this->dateLocaleToInternal($v, $dateFormat, true);
                $_filterDate = $localeDate->date($_filterDate, null, false);
                $_filterDate->add(new \DateInterval('P1D'));
                $_filterDate->sub(new \DateInterval('PT1S'));
                $_filterDate = datefmt_format_object($_filterDate, $datetimeFormatInt);
                $collection->addAttributeToFilter('main_table.created_at', ['lteq' => $_filterDate]);
            }

            if (!$r->getParam('apply_filter') && $vendor->getData('vendor_po_grid_status_filter')) {
                $filterStatuses = $vendor->getData('vendor_po_grid_status_filter');
                $filterStatuses = array_combine($filterStatuses, array_fill(0, count($filterStatuses), 1));
                $r->setParam('filter_status', $filterStatuses);
            }

            if (($v = $r->getParam('filter_method'))) {
                $collection->addAttributeToFilter('main_table.udropship_method', array('in' => array_keys($v)));
            }
            if (($v = $r->getParam('filter_status'))) {
                $collection->addAttributeToFilter('main_table.udropship_status', array('in' => array_keys($v)));
            }

            if (!$r->getParam('sort_by') && $vendor->getData('vendor_po_grid_sortby')) {
                $r->setParam('sort_by', $vendor->getData('vendor_po_grid_sortby'));
                $r->setParam('sort_dir', $vendor->getData('vendor_po_grid_sortdir'));
            }

            if (($v = $r->getParam('sort_by'))) {
                $map = array('order_date' => 'order_created_at', 'shipment_date' => 'created_at');
                if (isset($map[$v])) {
                    $v = $map[$v];
                }
                $collection->setOrder($v, $r->getParam('sort_dir'));
            }
            $this->_vendorShipmentCollection = $collection;
        }
        return $this->_vendorShipmentCollection;
    }

    public function mapField($field, $map)
    {
        return isset($map[$field]) ? $map[$field] : $field;
    }

    /**
     * Retrieve all shipping methods for carrier code
     *
     * Made for UPS which has CGI and XML methods
     *
     * @param string $carrierCode
     */
    public function getCarrierMethods($carrierCode, $allowedOnly = false)
    {
        if (empty($this->_carrierMethods[$allowedOnly][$carrierCode])) {
            $carrier = $this->_carrierFactory->create($carrierCode);
            $methods = array();
            if ($carrier) {
                if ($carrierCode == 'ups') {
                    $upsMethods = $this->src()
                        ->setPath('ups_shipping_method_combined')
                        ->toOptionHash();
                    $upsMethods = $upsMethods['UPS XML'] + $upsMethods['UPS CGI'];
                    if ($allowedOnly) {
                        $allowed = explode(',', $carrier->getConfigData('allowed_methods'));
                        $methods = array();
                        foreach ($allowed as $m) {
                            $methods[$m] = $upsMethods[$m];
                        }
                    } else {
                        $methods = $upsMethods;
                    }
                } else {
                    if ($allowedOnly) {
                        $methods = $carrier->getAllowedMethods();
                    } else {
                        try {
                            $methods = $carrier->getCode('methods');
                        } catch (\Exception $e) {
                            $methods = null;
                        }
                        if (!$methods) {
                            $methods = $carrier->getAllowedMethods();
                        }
                    }
                }
            }
            $this->_carrierMethods[$allowedOnly][$carrierCode] = $methods;
        }
        return $this->_carrierMethods[$allowedOnly][$carrierCode];
    }

    /**
     * Not used, for future use.
     *
     * @param mixed $allowedOnly
     */
    public function getAllCarriersMethods($allowedOnly = false)
    {
        $allCarrierMethods = array();
        $carrierNames = $this->src()->getCarriers();
        foreach ($carrierNames as $code => $carrier) {
            $allCarrierMethods[$code] = $this->getCarrierMethods($code, $allowedOnly);
        }
        return $allCarrierMethods;
    }

    public function getCarrierTitle($code)
    {
        $carrierNames = $this->src()->getCarriers();
        return !empty($carrierNames[$code]) ? $carrierNames[$code] : __('Unknown');
    }

    /**
     * Region cache
     *
     * @param integer $regionId
     * @return Region
     */
    public function getRegion($regionId)
    {
        if (!isset($this->_regions[$regionId])) {
            /* @var \Magento\Directory\Model\Region $region */
            $region = $this->createObj('\Magento\Directory\Model\Region');
            $this->_regions[$regionId] = $region->load($regionId);
        }
        return $this->_regions[$regionId];
    }

    protected $_countries = [];

    public function getCountry($countryId)
    {
        if (!isset($this->_countries[$countryId])) {
            /* @var \Magento\Directory\Model\Country $country */
            $country = $this->createObj('\Magento\Directory\Model\Country');
            $this->_countries[$countryId] = $country->load($countryId);
        }
        return $this->_countries[$countryId];
    }

    /**
     * Get region code by region ID
     *
     * @param integer $regionId
     * @return string
     */
    public function getRegionCode($regionId)
    {
        return $this->getRegion($regionId)->getCode();
    }

    public function getCountryName($countryId)
    {
        return $this->getCountry($countryId)->getName();
    }

    public function getLabelCarrierInstance($carrierCode)
    {
        $carrierCode = strtolower($carrierCode);

        $labelConfig = $this->config()->getLabel($carrierCode);
        if (!$labelConfig) {
            throw new \Exception('This carrier is not supported for label printing (' . $carrierCode . ')');
        }

        $labelModel = $this->getObj((string)$labelConfig['model']);
        if (!$labelModel) {
            throw new \Exception('Invalid label model for this carrier (' . $carrierCode . ')');
        }

        return $labelModel;
    }

    public function getLabelTypeInstance($labelType)
    {
        $labelType = strtolower($labelType);

        $labelConfig = $this->config()->getLabelType($labelType);
        if (!$labelConfig) {
            throw new \Exception('This label type is not supported (' . $labelType . ')');
        }

        $labelModel = $this->getObj((string)$labelConfig['model']);
        if (!$labelModel) {
            throw new \Exception('Invalid label model for this type (' . $labelType . ')');
        }

        return $labelModel;
    }

    public function curlCall($url, $request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
#echo "<xmp>"; echo $response; exit;

        //check for error
        if (($error = curl_error($ch))) {
            throw new Exception(__('Error connecting to API: %1', $error));
        }
        curl_close($ch);

        return $response;
    }

    public function sendDownload($fileName, $content, $contentType)
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getObj('\Magento\Framework\App\Response\Http');
        $response
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', strlen($content))
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->setHeader('Last-Modified', date('r'))
            ->setBody($content)
            ->sendResponse();

        exit;
    }

    /**
     * Calculate total shipping price + handling fee
     *
     * For future use (doctab)
     *
     * @param float $cost
     * @param array $params
     */
    public function getShippingPriceWithHandlingFee($cost, array $params)
    {
        $numBoxes = !empty($params['num_boxes']) ? $params['num_boxes'] : 1;
        $handlingFee = $params['handling_fee'];
        $finalMethodPrice = 0;
        $handlingType = $params['handling_type'];
        if (!$handlingType) {
            $handlingType = AbstractCarrier::HANDLING_TYPE_FIXED;
        }
        $handlingAction = $params['handling_action'];
        if (!$handlingAction) {
            $handlingAction = AbstractCarrier::HANDLING_ACTION_PERORDER;
        }

        if ($handlingAction == AbstractCarrier::HANDLING_ACTION_PERPACKAGE) {
            if ($handlingType == AbstractCarrier::HANDLING_TYPE_PERCENT) {
                $finalMethodPrice = ($cost + ($cost * $handlingFee / 100)) * $numBoxes;
            } else {
                $finalMethodPrice = ($cost + $handlingFee) * $numBoxes;
            }
        } else {
            if ($handlingType == AbstractCarrier::HANDLING_TYPE_PERCENT) {
                $finalMethodPrice = ($cost * $numBoxes) + ($cost * $numBoxes * $handlingFee / 100);
            } else {
                $finalMethodPrice = ($cost * $numBoxes) + $handlingFee;
            }

        }
        return $finalMethodPrice;
    }

    public function usortByPosition($a, $b)
    {
        return (float)$a['position'] < (float)$b['position'] ? -1 : ((float)$a['position'] > (float)$b['position'] ? 1 : 0);
    }

    /**
     * vsprintf extended to use associated array key names
     *
     * @link http://us.php.net/manual/en/function.vsprintf.php#87031
     * @param string $format
     * @param array $data
     */
    public function vnsprintf($format, array $data)
    {
        preg_match_all('/ (?<!%) % ( (?: [[:alpha:]_-][[:alnum:]_-]* | ([-+])? [0-9]+ (?(2) (?:\.[0-9]+)? | \.[0-9]+ ) ) ) \$ [-+]? \'? .? -? [0-9]* (\.[0-9]+)? \w/x', $format, $match, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $offset = 0;
        $keys = array_keys($data);
        foreach ($match as &$value) {
            if (($key = array_search($value[1][0], $keys, TRUE)) !== FALSE
                || (is_numeric($value[1][0])
                    && ($key = array_search((int)$value[1][0], $keys, TRUE)) !== FALSE)
            ) {
                $len = strlen($value[1][0]);
                $format = substr_replace($format, 1 + $key, $offset + $value[1][1], $len);
                $offset -= $len - strlen(1 + $key);
            }
        }
        return vsprintf($format, $data);
    }

    protected $_storeOrigin = array();

    /**
     * For potential future use
     *
     * @param mixed $store
     * @param Vendor $object
     */
    protected function _setOriginAddress($store, $object = null)
    {
        if (!$this->getScopeConfig('udropship/vendor/tax_by_vendor', $store)) {
            return;
        }
        $origin = null;
        $store = $this->_storeManager->getStore($store);
        $sId = $store->getId();
        if (is_null($object)) {
            if (!empty($this->_storeOrigin[$sId])) {
                $origin = $this->_storeOrigin[$sId];
                $this->_storeOrigin[$sId] = array();
            }
        } else {
            if (empty($this->_storeOrigin[$sId])) {
                $this->_storeOrigin[$sId] = $this->getScopeConfig('shipping/origin', $store);
            }
            if ($object instanceof \Magento\Quote\Model\Quote\Item || $object instanceof \Magento\Quote\Model\Quote\Address\Item) {
                $object = $object->getProduct();
            }
            if ($object instanceof Product || is_numeric($object)) {
                $object = $this->getVendor($object);
            }
            $origin = array(
                'country_id' => $object->getCountryId(),
                'region_id' => $object->getRegionId(),
                'postcode' => $object->getZip(),
            );
        }
        if ($origin) {
            $root = $this->getScopeConfig("shipping/origin", $store->getCode());
            foreach (array('country_id', 'region_id', 'postcode') as $v) {
                $root->$v = $origin[$v];
            }
        }
    }

    protected $_store;
    protected $_oldStore;
    protected $_oldArea;
    protected $_oldDesign;
    protected $_oldTheme;

    protected $_isEmulating = false;

    public function setDesignStore($store = null, $area = null, $theme = null)
    {
        /** @var \Magento\Store\Model\App\Emulation $appEmulation */
        $appEmulation = $this->getObj('\Magento\Store\Model\App\Emulation');
        if (!is_null($store)) {
            if ($this->_isEmulating) {
                return $this;
            }
            $this->_isEmulating = true;
            $store = $this->_storeManager->getStore($store);
            $appEmulation->startEnvironmentEmulation($store->getId(), $area, true);
            if ($theme) {
                /** @var \Magento\Framework\View\DesignInterface $viewDesign */
                $viewDesign = $this->getObj('\Magento\Framework\View\DesignInterface');
                try {
                    $viewDesign->setDesignTheme($theme, $area);
                } catch (\Exception $e) {

                }
            }
        } else {
            if (!$this->_isEmulating) {
                return $this;
            }
            $appEmulation->stopEnvironmentEmulation();
            $this->_isEmulating = false;
        }

        return $this;
    }

    public function addAdminhtmlVersion($module = 'Unirgy_Dropship')
    {
        return;
        $layout = Mage::app()->getLayout();
        $version = (string)$this->getScopeConfig("modules/{$module}/version", 'default');

        $layout->getBlock('before_body_end')->append($layout->createBlock('Magento\Framework\Block\Text')->setText('
        <script type="text/javascript">$$(".legality")[0].insert({after:"' . $module . ' ver. ' . $version . ', "});</script>
    '));

        return $this;
    }


    public function addTo($obj, $key, $value)
    {
        $new = $obj->getData($key) + $value;
        $obj->setData($key, $new);
        return $new;
    }

    protected $_queue = array();

    public function resetQueue()
    {
        $this->_queue = array();
    }

    public function addToQueue($action)
    {
        $this->_queue[] = $action;
        return $this;
    }

    public function processQueue()
    {
        $transport = null;

        if ($this->getScopeConfig('udropship/misc/mail_transport') == 'sendmail') {
            $sendmail = true;
            $transport = new \Zend_Mail_Transport_Sendmail();
        } // Integrate with \Aschroder\SMTPPro
        elseif ($this->isModuleActive('Aschroder_SMTPPro')) {
            $smtppro = $this->_smtpprodata;
            $transport = $smtppro->getSMTPProTransport();
        } // Integrate with \Aschroder\Email
        elseif ($this->isModuleActive('Aschroder_Email')) {
            $email = ObjectManager::getInstance()->get('UNKNOWN\aschroder_email\data');
            $transport = $email->getTransport();
        } // Integrate with \Aschroder\GoogleAppsEmail
        elseif ($this->isModuleActive('Aschroder_GoogleAppsEmail')) {
            $googleappsemail = $this->_googleappsemaildata;
            $transport = $googleappsemail->getGoogleAppsEmailTransport();
        } // integrate with ArtsOnIT_AdvancedSmtp
        elseif ($this->isMageAdvancedsmtpActive()) {
            $advsmtp = $this->_advancedsmtpdata;
            $transport = $advsmtp->getTransport();
        }

        foreach ($this->_queue as $action) {
            if ($action instanceof \Zend_Mail) {
                /* @var $action \Zend_Mail */
                if (!empty($smtppro) && method_exists($smtppro, 'isReplyToStoreEmail') && $smtppro->isReplyToStoreEmail()) {
                    if (method_exists($action, 'setReplyTo')) {
                        $action->setReplyTo($action->getFrom());
                    } else {
                        $action->addHeader('Reply-To', $action->getFrom());
                    }
                }
                if (!empty($sendmail)) {
                    $transport->parameters = '-f' . $action->getFrom();
                }
                $action->send($transport);
            } elseif (is_array($action)) { //array($object, $method, $args)
                call_user_func_array(array($action[0], $action[1]), !empty($action[2]) ? $action[2] : array());
            }
        }
        $this->resetQueue();
        return $this;
    }

    public function isMageAdvancedsmtpActive()
    {
        return $this->isModuleActive('Magento_Advancedsmtp') && $this->getScopeConfig('advancedsmtp/settings/enabled');
    }

    public function getNewVendors($days = 30)
    {
        /* @var \Unirgy\Dropship\Model\ResourceModel\Vendor\Collection $vendors */
        $vendors = $this->createObj('\Unirgy\Dropship\Model\ResourceModel\Vendor\Collection');
        $vendors
            ->addFieldToFilter('created_at', array('gt' => date('Y-m-d', time() - $days * 86400)))
            ->addOrder('created_at', 'desc');
        return $vendors;
    }

    public function loadFilteredCustomData($obj, $filter)
    {
        return $this->_loadCustomData($obj, $filter);
    }

    public function loadCustomData($obj)
    {
        return $this->_loadCustomData($obj);
    }

    protected function _loadCustomData($obj, $filter = array())
    {
        // add custom vars
        if ($obj->getCustomVarsCombined()) {
            $varsCombined = $obj->getCustomVarsCombined();
            if (strpos($varsCombined, 'a:') === 0) {
                $vars = @unserialize($varsCombined);
            } elseif (strpos($varsCombined, '{') === 0) {
                $vars = \Zend_Json::decode($varsCombined);
            }
            if (!empty($vars) && is_array($vars)) {
                foreach ($vars as $vk => $vv) {
                    if (!in_array($vk, $filter)) {
                        $obj->setData($vk, $vv);
                    }
                }
            }
        }

        // add custom data
        if (($customData = $obj->getData('custom_data_combined'))) {
            $arr = preg_split('#={5}\s+([^=]+)\s+={5}#', $customData, -1, PREG_SPLIT_DELIM_CAPTURE);
            $data = array();
            for ($i = 1, $l = sizeof($arr); $i < $l; $i += 2) {
                $obj->setData(trim($arr[$i]), trim($arr[$i + 1]));
            }
        }

        // add custom vars defaults
        foreach ($this->config()->getField() as $code => $node) {
            $_key = @$node['name'] ? (string)@$node['name'] : $code;
            if ((string)@$node['type'] == 'disabled') {
                continue;
            }
            if (((string)@$node['type'] == 'image' || (string)@$node['type'] == 'file')
                && $obj->hasData($_key) && is_array($obj->getData($_key))
            ) {
                $arr = $obj->getData($_key);
                $obj->setData($_key, $arr['value']);
            }
            if (array_key_exists('default', $node) && !$obj->hasData($_key)) {
                if (@$node['type'] == 'multiselect') {
                    $defVals = explode(',', (string)$node['default']);
                    $obj->setData($_key, $defVals);
                } else {
                    $obj->setData($_key, (string)$node['default']);
                }
            }
        }

        return $this;
    }

    public function processPostMultiselects(&$data)
    {
        $fields = $this->config()->getField();

        $visible = $this->getScopeConfig('udropship/vendor/visible_preferences');
        $visible = $visible ? explode(',', $visible) : array();

        $isAdmin = $this->isAdmin();

        foreach ($fields as $code => $node) {
            if ((string)@$node['type'] == 'multiselect' && empty($data[$code]) && ($isAdmin || empty($visible) || in_array($code, $visible))) {
                $data[$code] = array();
            }
        }

        return $this;
    }

    public function isAdmin()
    {
        /** @var \Magento\Framework\App\State $appState */
        $appState = $this->getObj('\Magento\Framework\App\State');
        return $appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    public function processCustomVars($obj)
    {
        $customVars = array();

        foreach ($this->config()->getField() as $code => $node) {
            $_key = @$node['name'] ? (string)@$node['name'] : $code;
            switch ((string)@$node['type']) {
                case 'disabled':
                    continue;

                case  'image':
                case 'file':
                    if ($obj->hasData($_key) && is_array($obj->getData($_key))) {
                        $arr = $obj->getData($_key);
                        if (!empty($arr['delete'])) {
                            @unlink($this->_dirList->getPath('media') . '/' . strtr($arr['value'], '/', '/'));
                            $obj->hasImageUpload(true);
                            $obj->unsetData($_key);
                        } else {
                            $obj->setData($_key, $arr['value']);
                        }
                    }
                    break;

                case 'multiselect':
                    if ($obj->hasData($_key) && !is_array($obj->getData($_key))) {
                        $obj->setData($_key, (array)$obj->getData($_key));
                    }
                    break;
            }
            if ($obj->hasData($_key)) {
                $customVars[$_key] = $obj->getData($_key);
                $customVars[$code] = $obj->getData($_key);
            }
        }
        $obj->setCustomVarsCombined(\Zend_Json::encode($customVars));

        return $this;
    }

    public function addMessageOnce($message, $module = 'checkout', $method = 'addError')
    {
        $session = $this->getObj($module . '\Model\Session');
        $found = false;
        foreach ($session->getMessages(false) as $m) {
            if ($m->getCode() == $message) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $session->$method($message);
        }
        return $this;
    }

    public function getNextWorkDayTime($date = null)
    {
        $time = is_string($date) ? strtotime($date) : (is_int($date) ? $date : time());
        $y = date('Y', $time);
        // calculate federal holidays
        $holidays = array();
        // month/day (jan 1st). iteration/wday/month (3rd monday in january)
        $hdata = array('1/1'/*newyr*/, '7/4'/*jul4*/, '11/11'/*vet*/, '12/25'/*xmas*/, '3/1/1'/*mlk*/, '3/1/2'/*pres*/, '5/1/5'/*memo*/, '1/1/9'/*labor*/, '2/1/10'/*col*/, '4/4/11'/*thanks*/);
        foreach ($hdata as $h1) {
            $h = explode('/', $h1);
            if (sizeof($h) == 2) { // by date
                $htime = mktime(0, 0, 0, $h[0], $h[1], $y); // time of holiday
                $w = date('w', $htime); // get weekday of holiday
                $htime += $w == 0 ? 86400 : ($w == 6 ? -86400 : 0); // if weekend, adjust
            } else { // by weekday
                $htime = mktime(0, 0, 0, $h[2], 1, $y); // get 1st day of month
                $w = date('w', $htime); // weekday of first day of month
                $d = 1 + ($h[1] - $w + 7) % 7; // get to the 1st weekday
                for ($t = $htime, $i = 1; $i <= $h[0]; $i++, $d += 7) { // iterate to nth weekday
                    $t = mktime(0, 0, 0, $h[2], $d, $y); // get next weekday
                    if (date('n', $t) > $h[2]) break; // check that it's still in the same month
                    $htime = $t; // valid
                }
            }
            $holidays[] = $htime; // save the holiday
        }
        for ($i = 0; $i < 5; $i++, $time += 86400) { // 5 days should be enough to get to workday
            if (in_array(date('w', $time), array(0, 6))) continue; // skip weekends
            foreach ($holidays as $h) { // iterate through holidays
                if ($time >= $h && $time <= $h + 86400) continue 2; // skip holidays
            }
            break; // found the workday
        }
        return $time;
    }

    /**
     * Poll carriers tracking API
     *
     * @param mixed $tracks
     */
    public function collectTracking($tracks)
    {
        $requests = array();
        foreach ($tracks as $track) {
            $cCode = $track->getCarrierCode();
            if (!$cCode) {
                continue;
            }
            $vId = $track->getShipment()->getUdropshipVendor();
            $v = $this->getVendor($vId);
            if (!$v->getTrackApi($cCode) || !$v->getId()) {
                continue;
            }
            $requests[$cCode][$vId][$track->getNumber()][] = $track;
        }
        foreach ($requests as $cCode => $vendors) {
            foreach ($vendors as $vId => $trackIds) {
                $v = $this->getVendor($vId);
                $_track = null;
                foreach ($trackIds as $_trackId => $_tracks) {
                    foreach ($_tracks as $_track) break 2;
                }
                /* @var \Unirgy\Dropship\Helper\Label $lblHlp */
                $lblHlp = $this->getObj('\Unirgy\Dropship\Helper\Label');
                try {
                    if ($_track) $lblHlp->beforeShipmentLabel($v, $_track);
                    $result = $v->getTrackApi($cCode)->collectTracking($v, array_keys($trackIds));
                    if ($_track) $lblHlp->afterShipmentLabel($v, $_track);
                } catch (\Exception $e) {
                    if ($_track) $lblHlp->afterShipmentLabel($v, $_track);
                    $this->_processPollTrackingFailed($trackIds, $e);
                    continue;
                }
#print_r($result); echo "\n";
                $processTracks = array();
                foreach ($result as $trackId => $status) {
                    foreach ($trackIds[$trackId] as $track) {

                        if (in_array($status, array(Source::TRACK_STATUS_PENDING, Source::TRACK_STATUS_READY, Source::TRACK_STATUS_SHIPPED))) {
                            $repeatIn = $this->getScopeConfig('udropship/customer/repeat_poll_tracking', $track->getShipment()->getOrder()->getStoreId());
                            if ($repeatIn <= 0) {
                                $repeatIn = 12;
                            }
                            $repeatIn = $repeatIn * 60 * 60;
                            $track->setNextCheck(date('Y-m-d H:i:s', time() + $repeatIn))->save();
                            if ($status == Source::TRACK_STATUS_PENDING) continue;
                        }

                        if ($track->getUdropshipStatus() == Source::TRACK_STATUS_PENDING
                            || $status == Source::TRACK_STATUS_DELIVERED
                        ) {
                            $track->setUdropshipStatus($status);
                        }
                        if ($track->dataHasChangedFor('udropship_status')) {
                            switch ($status) {
                                case Source::TRACK_STATUS_READY:
                                    $this->addShipmentComment(
                                        $track->getShipment(),
                                        __('Tracking ID %1 was picked up from %2', $trackId, $v->getVendorName())
                                    );
                                    $track->getShipment()->save();
                                    break;

                                case Source::TRACK_STATUS_DELIVERED:
                                    $this->addShipmentComment(
                                        $track->getShipment(),
                                        __('Tracking ID %1 was delivered to customer', $trackId)
                                    );
                                    $track->getShipment()->save();
                                    break;
                            }
                            if (empty($processTracks[$track->getParentId()])) {
                                $processTracks[$track->getParentId()] = array();
                            }
                            $processTracks[$track->getParentId()][] = $track;
                        }
                    }
                }
                foreach ($processTracks as $_pTracks) {
                    try {
                        $this->processTrackStatus($_pTracks, true);
                    } catch (\Exception $e) {
                        $this->_processPollTrackingFailed($_pTracks, $e);
                        continue;
                    }
                }
            }
        }
    }

    protected function _processPollTrackingFailed($tracks, \Exception $e)
    {
        $tracksByStore = array();
        foreach ($tracks as $_track) {
            if (is_array($_track)) {
                foreach ($_track as $__track) {
                    $tracksByStore[$__track->getShipment()->getOrder()->getStoreId()][] = $__track;
                }
            } elseif ($_track instanceof Track) {
                $tracksByStore[$_track->getShipment()->getOrder()->getStoreId()][] = $_track;
            }
        }
        foreach ($tracksByStore as $_sId => $_tracks) {
            /* @var \Unirgy\Dropship\Helper\Error $errHlp */
            $errHlp = $this->getObj('\Unirgy\Dropship\Helper\Error');
            $errHlp->sendPollTrackingFailedNotification($_tracks, "$e", $_sId);
        }
        return $this;
    }

    /**
     * Sending email with Invoice data
     *
     */
    public function sendTrackingNotificationEmail($track, $comment = '')
    {
        if (is_array($track)) {
            $tracks = $track;
            reset($tracks);
            $track = current($track);
        } else {
            $tracks = array($track);
        }
        $shipment = $track->getShipment();
        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();

        /* @var \Magento\Sales\Helper\Data $salesHelperData */
        $salesHelperData = $this->getObj('\Magento\Sales\Helper\Data');
        if (!$salesHelperData->canSendNewShipmentEmail($storeId)) {
            return $this;
        }

        $this->inlineTranslation->suspend();

        $copyTo = $this->getScopeConfig(\Magento\Sales\Model\Order\Email\Container\ShipmentIdentity::XML_PATH_EMAIL_COPY_TO, $storeId);
        if (!empty($copyTo)) {
            $copyTo = explode(',', $copyTo);
        }
        $copyMethod = $this->getScopeConfig(\Magento\Sales\Model\Order\Email\Container\ShipmentIdentity::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        /* @var \Magento\Payment\Helper\Data $paymentHlp */
        $paymentHlp = $this->getObj('\Magento\Payment\Helper\Data');
        $paymentBlock = $paymentHlp->getInfoBlock($order->getPayment());
        $paymentBlock->setIsSecureMode(true);

        $data = [
            'order' => $order,
            'shipment' => $shipment,
            'track' => $track,
            'tracks' => $tracks,
            'comment' => $comment,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $paymentBlock->toHtml()
        ];

        if ($order->getCustomerIsGuest()) {
            $template = $this->getScopeConfig('udropship/customer/tracking_email_template_guest', $storeId);
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $template = $this->getScopeConfig('udropship/customer/tracking_email_template', $storeId);
            $customerName = $order->getCustomerName();
        }

        $sendTo[] = array(
            'name' => $customerName,
            'email' => $order->getCustomerEmail()
        );
        if ($copyTo && $copyMethod == 'bcc') {
            foreach ($copyTo as $email) {
                $data['_BCC'][] = $email;
            }
        }

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'name' => null,
                    'email' => $email
                );
            }
        }

        foreach ($sendTo as $recipient) {
            $this->_transportBuilder->setTemplateIdentifier(
                $template
            )->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ]
            )->setTemplateVars(
                $data
            )->setFrom(
                $this->getScopeConfig(\Magento\Sales\Model\Order\Email\Container\ShipmentIdentity::XML_PATH_EMAIL_IDENTITY, $storeId)
            )->addTo(
                $recipient['email'],
                $recipient['name']
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        }

        $this->inlineTranslation->resume();

        return $this;
    }

    protected function _processTrackStatusSave($save, $object)
    {
        if ($object instanceof \Magento\Sales\Model\Order\Item) {
            $object->setProductOptions($object->getProductOptions());
        }
        if ($save === true) {
            $object->save();
        } elseif ($save instanceof \Magento\Framework\DB\Transaction) {
            $save->addObject($object);
        }
    }

    /**
     * Process tracking status update
     *
     * Will process only tracks with TRACK_STATUS_READY status
     *
     * @param Track $track
     * @param boolean|Transaction $save
     * @param null|boolean $complete
     */
    public function processTrackStatus($track, $save = false, $complete = null)
    {
        if (is_array($track)) {
            $tracks = $track;
            reset($tracks);
            $track = current($track);
        } else {
            $tracks = array($track);
        }
        $shipment = $track->getShipment();

        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();

        $saveShipment = false;
        $saveOrder = false; //not used yet

        $notifyTracks = array();

        foreach ($tracks as $track) {
            $saveTrack = false;

            // is the track ready to be marked as shipped
            $trackReady = $track->getUdropshipStatus() === Source::TRACK_STATUS_READY;
            // is the track shipped
            $shipped = $track->getUdropshipStatus() == Source::TRACK_STATUS_SHIPPED;
            // is the track delivered
            $delivered = $track->getUdropshipStatus() === Source::TRACK_STATUS_DELIVERED;

            // actions that need to be done if the track is not marked as shipped yet
            if (!$shipped) {
                // if new track record, set initial values
                if (!$track->getUdropshipStatus()) {
                    $vendorId = $shipment->getUdropshipVendor();
                    $pollTracking = $this->getScopeConfig('udropship/customer/poll_tracking', $storeId);
                    $trackApi = $this->getVendor($vendorId)->getTrackApi($track->getCarrierCode());
                    if ($pollTracking && $trackApi) {
                        $track->setUdropshipStatus(Source::TRACK_STATUS_PENDING);
                        $repeatIn = $this->getScopeConfig('udropship/customer/repeat_poll_tracking', $track->getShipment()->getOrder()->getStoreId());
                        if ($repeatIn <= 0) {
                            $repeatIn = 12;
                        }
                        $repeatIn = $repeatIn * 60 * 60;
                        $track->setNextCheck(date('Y-m-d H:i:s', time() + $repeatIn));
                    } else {
                        $track->setUdropshipStatus(Source::TRACK_STATUS_READY);
                    }
                    $saveTrack = true;
                }
                if ($track->getUdropshipStatus() == Source::TRACK_STATUS_READY) {
                    $track->setUdropshipStatus(Source::TRACK_STATUS_SHIPPED);
                    $notifyTracks[] = $track;
                    $saveTrack = true;
                }
                if ($delivered) {
                    $saveTrack = true;
                }
                if ($saveTrack) {
                    $this->_processTrackStatusSave($save, $track);
                }
            }
        }

        if (!empty($notifyTracks)) {
            $notifyOnOld = $this->getScopeConfig('udropship/customer/notify_on', $storeId);
            $notifyOn = $this->getScopeConfig('udropship/customer/notify_on_tracking', $storeId);
            if ($notifyOn) {
                $this->sendTrackingNotificationEmail($notifyTracks);
                $shipment->setEmailSent(true);
                $saveShipment = true;
            } elseif ($notifyOnOld == Source::NOTIFYON_TRACK) {
                /** @var \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender */
                $shipmentSender = $this->getObj('\Magento\Sales\Model\Order\Email\Sender\ShipmentSender');
                $shipmentSender->send($shipment);
                $shipment->setEmailSent(true);
                $saveShipment = true;
            }
        }

        $delivered = false;
        if ($shipment->getUdropshipStatus() != Source::SHIPMENT_STATUS_DELIVERED) {
            /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $nonDeliveredTracks */
            $nonDeliveredTracks = $this->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
            $nonDeliveredTracks
                ->setShipmentFilter($shipment->getId())
                ->addAttributeToFilter('udropship_status', array('nin' => array(Source::TRACK_STATUS_DELIVERED)));
            /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $deliveredTracks */
            $deliveredTracks = $this->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
            $deliveredTracks
                ->setShipmentFilter($shipment->getId())
                ->addAttributeToFilter('udropship_status', array('in' => array(Source::TRACK_STATUS_DELIVERED)));
            if (!$nonDeliveredTracks->count() && $deliveredTracks->count()) {
                $delivered = true;
            }
        }

        if ($shipment->getUdropshipStatus() == Source::SHIPMENT_STATUS_SHIPPED || $shipment->getUdropshipStatus() == Source::SHIPMENT_STATUS_DELIVERED) {
            if ($delivered && $shipment->getUdropshipStatus() != Source::SHIPMENT_STATUS_DELIVERED) {
                $this->processShipmentStatusSave(
                    $shipment, Source::SHIPMENT_STATUS_DELIVERED);
                $this->completeUdpoIfShipped($shipment, true);
            }
            return $this;
        }

        if (is_null($complete)) {
            if ($this->getScopeFlag('udropship/vendor/auto_shipment_complete', $storeId)) {
                switch ($this->getScopeFlag('udropship/vendor/auto_shipment_complete', $storeId)) {
                    case Source::AUTO_SHIPMENT_COMPLETE_ANY:
                        /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $pickedUpTracks */
                        $pickedUpTracks = $this->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
                        $pickedUpTracks
                            ->setShipmentFilter($shipment->getId())
                            ->addAttributeToFilter('udropship_status', array('in' => array(Source::TRACK_STATUS_SHIPPED, Source::TRACK_STATUS_DELIVERED)));
                        $complete = $pickedUpTracks->count() > 0;
                        break;
                    default:
                        /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection $pendingTracks */
                        $pendingTracks = $this->createObj('\Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection');
                        $pendingTracks
                            ->setShipmentFilter($shipment->getId())
                            ->addAttributeToFilter('udropship_status', array('nin' => array(Source::TRACK_STATUS_SHIPPED, Source::TRACK_STATUS_DELIVERED)));
                        $complete = !$pendingTracks->count();
                        break;
                }
            } else {
                $complete = false;
            }
        }

        if ($complete) {
            $this->completeShipment($shipment, $save, $delivered);
            $saveShipment = true;
        } elseif ($shipment->getUdropshipStatus() != Source::SHIPMENT_STATUS_PARTIAL) {

            $shipment->setUdropshipStatus(Source::SHIPMENT_STATUS_PARTIAL);
            $saveShipment = true;
        }
        if ($saveShipment) {
            foreach ($shipment->getAllTracks() as $t) {
                foreach ($tracks as $_t) {
                    if ($t->getEntityId() == $_t->getEntityId()) {
                        $t->setData($_t->getData());
                        break;
                    }
                }
            }
            $this->_processTrackStatusSave($save, $shipment);
        }

        if ($complete) {
            $this->completeUdpoIfShipped($shipment, $save);
            $this->completeOrderIfShipped($shipment, $save);
        }

        return $this;
    }

    public function registerShipmentItem($item, $save)
    {
        if ($this->isUdpoActive()) {
            $this->udpoHlp()->completeShipmentItem($item, $save);
        } else {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy(true)) {
                $item->setQty(1);
            }
            if ($item->getQty() > 0) {
                $item->getOrderItem()->setQtyShipped(
                    min($item->getOrderItem()->getQtyShipped() + $item->getQty(), $item->getOrderItem()->getQtyOrdered())
                );
                $this->_processTrackStatusSave($save, $orderItem);
            }
        }
    }

    public function completeShipment($shipment, $save = false, $delivered = false)
    {
        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();

        $newStatus = $delivered
            ? Source::SHIPMENT_STATUS_DELIVERED
            : Source::SHIPMENT_STATUS_SHIPPED;

        if ($newStatus == $shipment->getUdropshipStatus()) {
            return $this;
        }
        $shipment->setUdropshipStatus($newStatus);
        $this->addShipmentComment(
            $shipment,
            __('Shipment has been complete')
        );

        foreach ($shipment->getAllItems() as $item) {
            $this->registerShipmentItem($item, $save);
        }

        $shipment->getCommentsCollection()->save();

        $notifyOnOld = $this->getScopeConfig('udropship/customer/notify_on', $storeId);
        $notifyOn = $this->getScopeConfig('udropship/customer/notify_on_shipment', $storeId);
        if (($notifyOn || $notifyOnOld == Source::NOTIFYON_SHIPMENT) && !$delivered) {
            $shipmentSender = $this->getObj('\Magento\Sales\Model\Order\Email\Sender\ShipmentSender');
            $shipmentSender->send($shipment);
            $shipment->setEmailSent(true);
        }

        $this->_processTrackStatusSave($save, $shipment);

        if ($this->isUdpoActive()) {
            $this->udpoHlp()->completeShipment($shipment, $save);
        }

        return $this;
    }

    public function completeUdpoIfShipped($shipment, $save = false, $force = true)
    {
        if ($this->isUdpoActive()) {
            $this->udpoHlp()->completeUdpoIfShipped($shipment, $save, $force);
        }
    }

    public function completeOrderIfShipped($shipment, $save = false, $force = true)
    {
        $order = $shipment->getOrder();

        /* @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $pendingShipments */
        $pendingShipments = $this->createObj('\Magento\Sales\Model\Order\Shipment')->getCollection();
        $pendingShipments->setOrderFilter($order->getId())
            ->addAttributeToFilter('entity_id', array('neq' => $shipment->getId()))
            ->addAttributeToFilter('udropship_status', array('nin' => array(Source::SHIPMENT_STATUS_SHIPPED, Source::SHIPMENT_STATUS_DELIVERED)));

        if (!$pendingShipments->count() && $force) {
            // will not work with 1.4.x
            #$order->setState(Order::STATE_COMPLETE, true);
        }
        $order->setIsInProcess(true);
        $this->_processTrackStatusSave($save, $order);

        return $this;
    }

    public function getVendorSku($item)
    {
        if ($this->isUdmultiActive()) {
            if ($item->getOrderItem()) {
                $item = $item->getOrderItem();
            }
            return $this->udmultiHlp()->getVendorSku($item->getProductId(), $item->getUdropshipVendor(), $item->getSku());
        } else {
            return $item->getSku();
        }
    }

    /**
     * @var \Unirgy\Dropship\Model\ResourceModel\Shipping\Collection
     */
    protected $_shippingMethods;

    public function getShippingMethods()
    {
        if (!$this->_shippingMethods) {
            $this->_shippingMethods = $this->createObj('\Unirgy\Dropship\Model\ResourceModel\Shipping\Collection');
        }
        return $this->_shippingMethods;
    }

    protected $_systemShippingMethods;

    public function getSystemShippingMethods()
    {
        if (!$this->_systemShippingMethods) {
            $systemMethods = array();
            $shipping = $this->getShippingMethods();
            foreach ($shipping as $s) {
                if (!$s->getSystemMethods()) {
                    continue;
                }
                foreach ($s->getSystemMethods() as $c => $m) {
                    $systemMethods[$c][$m] = $s;
                }
            }
            $this->_systemShippingMethods = $systemMethods;
        }
        return $this->_systemShippingMethods;
    }

    protected $_multiSystemShippingMethods;

    public function getMultiSystemShippingMethods()
    {
        if (!$this->_multiSystemShippingMethods) {
            $systemMethods = array();
            $shipping = $this->getShippingMethods();
            foreach ($shipping as $s) {
                if (!$s->getSystemMethods()) {
                    continue;
                }
                foreach ($s->getSystemMethods() as $c => $m) {
                    if (empty($systemMethods[$c][$m])) {
                        $systemMethods[$c][$m] = array();
                    }
                    $systemMethods[$c][$m][] = $s;
                }
            }
            $this->_multiSystemShippingMethods = $systemMethods;
        }
        return $this->_multiSystemShippingMethods;
    }

    protected $_multiSystemShippingMethodsByProfile;

    public function getMultiSystemShippingMethodsByProfile($profile)
    {
        $vendorCustom = false;
        if ($profile instanceof Vendor
            && $this->isUdsprofileActive()
        ) {
            if ($profile->getShippingProfileUseCustom()) {
                $vendor = $profile;
                $vendorCustom = true;
            }
            $profile = $profile->getShippingProfile();
        } elseif ($profile instanceof DataObject) {
            $profile = $profile->getShippingProfile();
        }
        if (empty($profile)
            || !$this->isUdsprofileActive()
            || !$this->udsprofileHlp()->hasProfile($profile)
        ) {
            $profile = 'default';
        }
        $cacheKey = $profile;
        if ($vendorCustom) {
            $cacheKey = 'vendor_custom_' . $vendor->getId();
        }
        if (!isset($this->_multiSystemShippingMethodsByProfile[$cacheKey])) {
            $systemMethods = array();
            $shipping = $this->getShippingMethods();
            foreach ($shipping as $s) {
                $s->useProfile($vendorCustom ? $vendor : $profile);
                if (!$s->getAllSystemMethods()) {
                    continue;
                }
                foreach ($s->getAllSystemMethods() as $c => $m) {
                    if (empty($m)) continue;
                    if (!is_array($m)) {
                        $m = array($m => $m);
                    }
                    foreach ($m as $__m) {
                        if (empty($systemMethods[$c][$__m])) {
                            $systemMethods[$c][$__m] = array();
                        }
                        $systemMethods[$c][$__m][] = $s;
                    }
                }
            }
            foreach ($shipping as $s) {
                $s->resetProfile();
            }
            $this->_multiSystemShippingMethodsByProfile[$cacheKey] = $systemMethods;
        }
        return $this->_multiSystemShippingMethodsByProfile[$cacheKey];
    }

    public function saveThisVendorProducts($data, $v)
    {
        return $this->_saveVendorProducts($data, $v);
    }

    public function saveVendorProducts($data)
    {
        return $this->_saveVendorProducts($data, $this->session()->getVendor());
    }

    protected function _saveVendorProducts($data, $v)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $this->_oldStoreId = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore(0);

        /* @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
        $ptAlias = \Magento\Catalog\Model\ResourceModel\Product\Collection::MAIN_TABLE_ALIAS;
        $products = $this->createObj('\Unirgy\Dropship\Model\ResourceModel\ProductCollection')
            ->setFlag('udskip_price_index',1)
            ->setFlag('has_stock_status_filter', 1)
            ->addAttributeToSelect('cost')
            ->addIdFilter(array_keys($data));

        $vsAttrCode = $this->getScopeConfig('udropship/vendor/vendor_sku_attribute');
        if (($hasVsAttr = $this->checkProductAttribute($vsAttrCode)) && ($hasVsAttr = $hasVsAttr && ($vsAttrCode != 'sku'))) {
            $products->addAttributeToSelect($vsAttrCode);
            $vsAttr = $this->_eavConfig->getAttribute('catalog_product', $vsAttrCode);
        }

        if ($v->getId() == $this->getScopeConfig('udropship/vendor/local_vendor')) {
            $attr = $this->_eavConfig->getAttribute('catalog_product', 'udropship_vendor');
            $products->getSelect()->joinLeft(
                array('_udv' => $attr->getBackend()->getTable()),
                '_udv.'.$this->rowIdField().'='.$ptAlias.'.'.$this->rowIdField().' and _udv.store_id=0 and _udv.attribute_id=' . $attr->getId() . ' and _udv.value=' . $v->getId(),
                array('udropship_vendor' => 'value')
            );
        } else {
            $products->addAttributeToFilter('udropship_vendor', $v->getId());
        }

        $products->load();
        $this->_storeManager->setCurrentStore($this->_oldStoreId);

        if (!$products) {
            return false;
        }
        //$this->_modelStock->addItemsToProducts($products);

        $cnt = 0;
        foreach ($products as $p) {
            if (empty($data[$p->getId()])) {
                continue;
            }
            $d = $data[$p->getId()];
            $updateProduct = false;
            $updateStock = false;
            /*
        if ($p->hasCost() && empty($d['vendor_cost'])) {
            $p->unsCost();
            $updateProduct = true;
        } elseif (!empty($d['vendor_cost']) && $d['vendor_cost']!=$p->getCost()) {
            $p->setCost($d['vendor_cost']);
            $updateProduct = true;
        }
        */
            $ps = $this->_stockRegistry->getStockItem($p->getId());
            if (isset($d['stock_status']) && $d['stock_status'] != $ps->getIsInStock()) {
                $ps->setIsInStock($d['stock_status']);
                $updateStock = true;
            }
            if (isset($d['stock_qty']) && $d['stock_qty'] != $ps->getQty()) {
                $ps->setQty($d['stock_qty']);
                $updateStock = true;
            } elseif (!isset($d['stock_qty']) && isset($d['stock_qty_add'])) {
                $ps->setQty($ps->getQty() + $d['stock_qty_add']);
                $updateStock = true;
            }
            if ($hasVsAttr && isset($d['vendor_sku']) && $d['vendor_sku'] != $p->getData($vsAttrCode)) {
                $p->setData($vsAttrCode, $d['vendor_sku']);
                $p->getResource()->saveAttribute($p, $vsAttrCode);
            }
            if ($updateProduct) {
                $p->save();
            }
            if ($updateStock) {
                $ps->save();
            }
            if ($updateProduct || $updateStock) {
                $cnt++;
            }
        }
        return $cnt;
    }

    public function compareMageVer($ceVer, $eeVer = null, $op = '>=')
    {
        $version = $this->appProductMeta->getVersion();
        $eeVer = is_null($eeVer) ? $ceVer : $eeVer;
        return $this->isEE()
            ? version_compare($version, $eeVer, $op)
            : version_compare($version, $ceVer, $op);
    }

    public function isEE()
    {
        return strtolower($this->appProductMeta->getEdition()) === 'enterprise';
    }

    protected $_hasMageFeature = array();

    public function hasMageFeature($feature)
    {
        if (!isset($this->_hasMageFeature[$feature])) {
            $flag = false;
            switch ($feature) {
                case 'scope_code_resolver':
                    $flag = $this->compareMageVer('2.1.3');
                    break;
                case 'row_id':
                    $flag = $this->isEE() && $this->compareMageVer('2.1', '2.1');
                    break;
                case 'stock_website':
                    $flag = $this->compareMageVer('2.1');
                    break;
            }
            $this->_hasMageFeature[$feature] = $flag;
        }
        return $this->_hasMageFeature[$feature];
    }

    public function rowIdField()
    {
        return $this->hasMageFeature('row_id') ? 'row_id' : 'entity_id';
    }
    public function trackNumberField()
    {
        return 'track_number';
    }

    public function isSalesFlat()
    {
        return true;
    }

    public function isWysiwygAllowed()
    {
        return true;
    }

    public function assignVendorSkus($po)
    {
        $storeId = $po->getStoreId();
        $attr = $this->getScopeConfig('udropship/vendor/vendor_sku_attribute', $storeId);
        $this->addVendorSkus($po);
        foreach ($po->getAllItems() as $item) {
            $oItem = $item->getOrderItem();
            $oItemParent = $oItem->getParentItem();
            $item->setData('__orig_sku', $item->getSku());
            $oItem->setData('__orig_sku', $oItem->getSku());
            if ($item->getVendorSku()) {
                $item->setSku($item->getVendorSku());
                if ($oItem->getProductType() == 'bundle' || ($oItemParent && $oItemParent->getProductType() == 'bundle')) {
                    $oItem->setSku($item->getVendorSku());
                }
            }
            $pOpts = $item->getOrderItem()->getProductOptions();
            $pOpts = is_string($pOpts) ? unserialize($pOpts) : $pOpts;
            if (is_array($pOpts) && !empty($pOpts['simple_sku']) && $item->getVendorSimpleSku()) {
                $item->setData('__orig_simple_sku', $pOpts['simple_sku']);
                $pOpts['simple_sku'] = $item->getVendorSimpleSku();
                $item->setSku($item->getVendorSimpleSku());
                $oItem->setSku($item->getVendorSimpleSku());
                $item->getOrderItem()->setProductOptions($pOpts);
            }
        }
        if ($po instanceof Shipment) {
            $this->_eventManager->dispatch('udropship_shipment_assign_vendor_skus', array('shipment' => $po, 'attribute_code' => $attr));
        } elseif ($po instanceof \Unirgy\DropshipPo\Model\Po) {
            $this->_eventManager->dispatch('udpo_po_assign_vendor_skus', array('udpo' => $po, 'attribute_code' => $attr));
        }
        return $this;
    }

    public function unassignVendorSkus($po)
    {
        $storeId = $po->getStoreId();
        $attr = $this->getScopeConfig('udropship/vendor/vendor_sku_attribute', $storeId);
        //if ($attr && $attr!='sku') {
        foreach ($po->getAllItems() as $item) {
            $oItem = $item->getOrderItem();
            $oItemParent = $oItem->getParentItem();
            if ($item->hasData('__orig_sku')) {
                $item->setSku($item->getData('__orig_sku'));
                if ($oItem->getProductType() == 'bundle' || ($oItemParent && $oItemParent->getProductType() == 'bundle')) {
                    $oItem->setSku($oItem->getData('__orig_sku'));
                }
            }
            if ($oItem->hasData('__orig_sku')) {
                $oItem->setSku($oItem->getData('__orig_sku'));
            }
            $pOpts = $item->getOrderItem()->getProductOptions();
            if ($item->hasData('__orig_simple_sku')) {
                $pOpts['simple_sku'] = $item->getData('__orig_simple_sku');
            }
            $item->getOrderItem()->setProductOptions($pOpts);
        }
        //}
        if ($po instanceof Shipment) {
            $this->_eventManager->dispatch('udropship_shipment_unassign_vendor_skus', array('udpo' => $po, 'attribute_code' => $attr));
        } elseif ($po instanceof \Unirgy\DropshipPo\Model\Po) {
            $this->_eventManager->dispatch('udpo_po_unassign_vendor_skus', array('udpo' => $po, 'attribute_code' => $attr));
        }
        return $this;
    }

    public function addVendorSkus($po)
    {
        $storeId = $po->getStoreId();
        $attr = $this->getScopeConfig('udropship/vendor/vendor_sku_attribute', $storeId);
        $productIds = array();
        $simpleSkus = array();
        $urlPids = array();
        foreach ($po->getAllItems() as $item) {
            if (null === $item->getData('vendor_sku')) {
                $item->setFirstAddVendorSkuFlag(true);
                $productIds[] = $item->getProductId();
            }
            if ($item->getOrderItem()->getProductOptionByCode('simple_sku')) {
                if (null === $item->getData('vendor_simple_sku')) {
                    $item->setFirstAddVendorSimpleSkuFlag(true);
                    $simpleSkus[spl_object_hash($item)] = $item->getOrderItem()->getProductOptionByCode('simple_sku');
                }
            }
            if (!$item->getProduct() instanceof Product) {
                $urlPids[] = $item->getProductId();
            }
        }
        if ($urlPids) {
            $_oldStoreId = $this->_storeManager->getStore()->getId();
            $this->_storeManager->setCurrentStore($this->getDefaultStoreView());
            /* @var \Magento\Catalog\Model\ResourceModel\Product\Collection $urlProducts */
            $urlProducts = $this->createObj('\Magento\Catalog\Model\Product')->getCollection();
            $urlProducts->setStoreId($storeId)
                ->addIdFilter($urlPids)
                ->addAttributeToSelect('url_key')
                ->addUrlRewrite();
            $urlProducts->load();
            $this->_storeManager->setCurrentStore($_oldStoreId);
            foreach ($po->getItemsCollection() as $item) {
                if ($product = $urlProducts->getItemById($item->getProductId())) {
                    if (!$item->getProduct()) {
                        $item->setProduct($product);
                    }
                }
            }
        }
        if ($attr && $attr != 'sku' && $this->checkProductAttribute($attr)) {
            $attrFilters = array();
            if (!empty($productIds)) {
                $attrFilters[] = array('attribute' => 'entity_id', 'in' => array_values($productIds));
            }
            if (!empty($simpleSkus)) {
                $attrFilters[] = array('attribute' => 'sku', 'in' => array_values($simpleSkus));
            }
            if (!empty($attrFilters)) {
                /* @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
                $products = $this->createObj('\Magento\Catalog\Model\Product')->getCollection();
                $products->setStoreId($storeId)
                    ->addAttributeToSelect($attr)
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('sku_type')
                    ->addAttributeToSelect('url_key')
                    ->addAttributeToFilter($attrFilters);
                foreach ($po->getAllItems() as $item) {
                    $oItem = $item->getOrderItem();
                    if (null === $item->getData('vendor_sku')
                        && ($product = $products->getItemById($item->getProductId()))
                    ) {
                        $pSku = $product->getSku();
                        $iSku = $item->getSku();
                        $psLen = strlen($pSku);
                        $isLen = strlen($iSku);
                        $optSku = '';
                        if ($isLen > $psLen && substr($iSku, 0, $psLen) == $pSku) {
                            $optSku = substr($iSku, $psLen);
                        }
                        $item->setVendorSku($product->getData($attr));
                        $item->setData('__opt_sku', $optSku);
                        $item->setVendorSku($item->getVendorSku() . $optSku);
                        if ($oItem->getProductType() == 'bundle' && !$product->getSkuType() && $oItem->getChildrenItems()) {
                            $_bundleSkus = array($product->getData($attr) ? $product->getData($attr) : $product->getSku());
                            foreach ($oItem->getChildrenItems() as $oiChild) {
                                if (($childProd = $products->getItemById($oiChild->getProductId()))
                                    && $childProd->getData($attr)
                                ) {
                                    $_bundleSkus[] = $childProd->getData($attr);
                                } else {
                                    $_bundleSkus[] = $oiChild->getSku();
                                }
                            }
                            $item->setVendorSku(implode('-', $_bundleSkus));
                        }
                    } elseif (null === $item->getData('vendor_sku')) {
                        $item->setVendorSku('');
                    }
                    if (null === $item->getData('vendor_simple_sku') && !empty($simpleSkus[spl_object_hash($item)])
                        && $item->getOrderItem()->getProductOptionByCode('simple_sku')
                        && ($product = $products->getItemByColumnValue('sku', $simpleSkus[spl_object_hash($item)]))
                        && $product->getData($attr)
                    ) {
                        $pSku = $product->getSku();
                        $iSku = $item->getSku();
                        $psLen = strlen($pSku);
                        $isLen = strlen($iSku);
                        $optSku = '';
                        if ($isLen > $psLen && substr($iSku, 0, $psLen) == $pSku) {
                            $optSku = substr($iSku, $psLen);
                        }
                        $item->setData('__opt_sku', $optSku);
                        $item->setVendorSimpleSku((string)$product->getData($attr));
                        $item->setVendorSimpleSku($item->getVendorSimpleSku() . $optSku);
                        if ($product->getData($attr)) {
                            $item->setVendorSku((string)$product->getData($attr));
                            $item->setVendorSku($item->getVendorSku() . $optSku);
                        }
                    } elseif (null === $item->getData('vendor_simple_sku')) {
                        $item->setVendorSimpleSku('');
                    }
                }
            }
        }
        $this->_eventManager->dispatch('udropship_po_add_vendor_skus', array('po' => $po, 'attribute_code' => $attr));
        foreach ($po->getAllItems() as $item) {
            $item->unsFirstAddVendorSkuFlag();
        }
        return $this;
    }

    public function getVendorShipmentsPdf($shipments)
    {
        foreach ($shipments as $shipment) {
            $this->assignVendorSkus($shipment);
            $tracks = $shipment->getOrder()->getTracksCollection();
            $tracks->load();
            foreach ($tracks as $id => $track) {
                $tracks->removeItemByKey($id);
            }
            if ($shipment->getUdropshipMethodDescription()) {
                $shipment->getOrder()->setData('__orig_shipping_description', $shipment->getOrder()->getShippingDescription());
                $shipment->getOrder()->setShippingDescription($shipment->getUdropshipMethodDescription());
            }
        }
        /* @var \Unirgy\Dropship\Model\Pdf\Shipment $pdf */
        $pdf = $this->createObj('\Unirgy\Dropship\Model\Pdf\Shipment')
            ->setUseFont($this->getScopeConfig('udropship/vendor/pdf_use_font'))
            ->getPdf($shipments);
        foreach ($shipments as $shipment) {
            if ($shipment->getOrder()->hasData('__orig_shipping_description')) {
                $shipment->getOrder()->setShippingDescription($shipment->getOrder()->getData('__orig_shipping_description'));
                $shipment->getOrder()->unsetData('__orig_shipping_description');
            }
            $this->unassignVendorSkus($shipment);
        }
        return $pdf;
    }

    protected $_shipmentComments = array();

    public function getVendorShipmentsCommentsCollection($shipment)
    {
        if (!isset($this->_shipmentComments[$shipment->getId()])) {
            $comments = $this->createObj('\Magento\Sales\Model\Order\Shipment\Comment')->getCollection()
                ->setShipmentFilter($shipment->getId())
                ->addAttributeToFilter('is_visible_to_vendor', 1)
                ->setCreatedAtOrder();

            if ($shipment->getId()) {
                foreach ($comments as $comment) {
                    $comment->setShipment($shipment);
                }
            }
            $this->_shipmentComments[$shipment->getId()] = $comments;
        }
        return $this->_shipmentComments[$shipment->getId()];
    }

    public function applyEstimateTotalPriceMethod($total, $price, $store = null)
    {
        $totalMethod = $this->getScopeConfig('udropship/customer/estimate_total_method', $store);
        if ($totalMethod == 'max') {
            $total = max($total, $price);
        } else {
            $total += $price;
        }
        return $total;
    }

    public function applyEstimateTotalCostMethod($total, $cost)
    {
        $total += $cost;
        return $total;
    }

    public function explodeOrderShippingMethod($order)
    {
        $oShippingMethod = explode('_', $order->getShippingMethod(), 2);
        if (!empty($oShippingMethod[1])) {
            $_osm = explode('___', $oShippingMethod[1]);
            $oShippingMethod[1] = $_osm[0];
            if (!empty($_osm[1]) && false !== strpos($_osm[1], '_')) {
                $__osm = explode('___', $_osm[1]);
                $oShippingMethod[2] = $__osm[0];
            }
        }
        return $oShippingMethod;
    }

    public function initVendorShippingMethodsForHtmlSelect($order, &$vMethods)
    {
        $oShippingMethod = $this->explodeOrderShippingMethod($order);
        $carrierNames = $this->src()->getCarriers();
        $shipping = $this->getShippingMethods();
        if ('order' == $this->getScopeConfig('udropship/vendor/reassign_available_shipping')
            && $oShippingMethod[0] == 'udropship' && !empty($oShippingMethod[1])
        ) {
            $oShipping = $shipping->getItemByColumnValue('shipping_code', $oShippingMethod[1]);
        }
        $oShippingDetails = $this->unserialize($order->getUdropshipShippingDetails());
        foreach ($vMethods as $vId => &$vMethod) {
            if ($vMethod === false) continue;
            $v = $this->getVendor($vId);
            $vSMs = $v->getShippingMethods();
            foreach ($vSMs as $sId => $__vSM) {
                foreach ($__vSM as $vSM) {
                    if (isset($oShipping) && $sId != $oShipping->getId()) continue;
                    $s = $shipping->getItemById($sId);
                    $s->useProfile($v);
                    list($sc, $cc) = array($s->getShippingCode(), $vSM['carrier_code']);
                    $ccs = array($cc);
                    if ($cc != $v->getCarrierCode()) $ccs[] = $v->getCarrierCode();
                    foreach ($ccs as $i => $cc) {
                        $mc = !empty($vSM['method_code']) && $vSM['carrier_code'] == $cc
                            ? $vSM['method_code']
                            : $s->getSystemMethods($cc);
                        if (empty($sc) || empty($cc) || empty($mc)) continue;
                        $cMethodNames = $this->getCarrierMethods($cc);
                        if ($mc == '*') {
                            $_mc = is_array($cMethodNames) ? array_keys($cMethodNames) : array();
                        } else {
                            $_mc = array($mc);
                        }
                        foreach ($_mc as $mc) {
                            if (!isset($cMethodNames[$mc])) continue;
                            $vMethod[$sc]['__title'] = $s->getShippingTitle();
                            $ccMcKeys = array(sprintf('%s_%s', $cc, $mc));
                            if ($this->hasExtraChargeMethod($v, $vSM)) {
                                $ccMcKeys[] = sprintf('%s_%s___ext', $cc, $mc);
                            }
                            foreach ($ccMcKeys as $ccMcKey) {
                                if ($oShippingMethod[0] == 'udropship' && !empty($oShippingMethod[1])
                                    && $sc == $oShippingMethod[1]
                                    && is_array($oShippingDetails)
                                    && !empty($oShippingDetails['methods'][$vId]['code'])
                                    && $oShippingDetails['methods'][$vId]['code'] == $ccMcKey
                                ) {
                                    if (empty($oShippingMethod[2]) || $oShippingMethod[2] == $ccMcKey) {
                                        $vMethod[$sc][$ccMcKey]['__selected'] = true;
                                    }
                                } elseif ($oShippingMethod[0] == 'udsplit'
                                    && is_array($oShippingDetails)
                                    && !empty($oShippingDetails['methods'][$vId]['code'])
                                    && $oShippingDetails['methods'][$vId]['code'] == $ccMcKey
                                ) {
                                    $vMethod[$sc][$ccMcKey]['__selected'] = true;
                                }
                                if (false !== strpos($ccMcKey, '___ext')) {
                                    $vMethod[$sc][$ccMcKey][$ccMcKey] = sprintf('%s - %s %s', $carrierNames[$cc], $cMethodNames[$mc], $this->getExtraChargeData($v, $vSM, 'extra_charge_suffix'));
                                } else {
                                    $vMethod[$sc][$ccMcKey][$ccMcKey] = sprintf('%s - %s', $carrierNames[$cc], $cMethodNames[$mc]);
                                }
                            }
                        }
                    }
                    $s->resetProfile();
                }
            }
        }
        unset($vMethod);
    }

    public function createOnDuplicateExpr($conn, $fields)
    {
        $updateFields = array();
        foreach ($fields as $k => $v) {
            $field = $value = null;
            if (!is_numeric($k)) {
                $field = $conn->quoteIdentifier($k);
                if ($v instanceof \Zend_Db_Expr) {
                    $value = $v->__toString();
                } else if (is_string($v)) {
                    $value = 'VALUES(' . $conn->quoteIdentifier($v) . ')';
                } else if (is_numeric($v)) {
                    $value = $conn->quoteInto('?', $v);
                }
            } else if (is_string($v)) {
                $field = $conn->quoteIdentifier($v);
                $value = 'VALUES(' . $field . ')';
            }

            if ($field && $value) {
                $updateFields[] = "{$field}={$value}";
            }
        }
        return $updateFields ? (" ON DUPLICATE KEY UPDATE " . join(', ', $updateFields)) : '';
    }

    public function getAdjustmentPrefix($type)
    {
        switch ($type) {
            case 'po_comment':
                return 'po-comment-';
            case 'shipment_comment':
                return 'shipment-comment-';
            case 'statement':
                return 'statement-';
            case 'payout':
                return 'payout-';
            case 'statement:payout':
                return 'statement:payout-';
        }
        return '';
    }

    public function isAdjustmentComment($comment, $store = null)
    {
        $adjTrigger = $this->getScopeConfig('udropship/statement/adjustment_trigger', $store) . ':';
        $adjTriggerQ = preg_quote($adjTrigger);
        return preg_match("#({$adjTriggerQ})\\s*([0-9.-]+)\\s*(.*)\$#m", $comment);
    }

    public function collectPoAdjustments($pos, $force = false)
    {
        $adjTrigger = $this->getScopeConfig('udropship/statement/adjustment_trigger') . ':';
        $adjTriggerQ = preg_quote($adjTrigger);
        $posToCollect = array();
        foreach ($pos as $po) {
            if (!$po->hasAdjustments() || $force) {
                $posToCollect[$po->getId()] = $po;
            }
        }
        if (!empty($posToCollect)) {
            $poType = $pos instanceof DataCollection && $pos->getFirstItem() instanceof \Unirgy\DropshipPo\Model\Po
            || reset($pos) instanceof \Unirgy\DropshipPo\Model\Po
                ? 'po' : 'shipment';
            $comments = $adjustments = $adjAmounts = array();
            if ($poType == 'po') {
                $commentsCollection = $this->createObj('\Unirgy\DropshipPo\Model\Po\Comment')->getCollection()
                    ->addAttributeToFilter('parent_id', array('in' => array_keys($posToCollect)))
                    ->addAttributeToFilter('comment', array('like' => $adjTrigger . '%'))
                    ->addAttributeToSelect('*')
                    ->addAttributeToSort('created_at');
                $commentsCollection->getSelect()
                    ->columns(array('po_id' => 'parent_id', 'adjustment_prefix_type' => new \Zend_Db_Expr("'po_comment'")));
                $comments[] = $commentsCollection;
            }
            $commentsCollection = $this->createObj('\Magento\Sales\Model\Order\Shipment\Comment')->getCollection()
                ->addAttributeToFilter('comment', array('like' => $adjTrigger . '%'))
                ->addAttributeToSelect('*')
                ->addAttributeToSort('created_at');
            if ($poType == 'po') {
                $commentsCollection->getSelect()->join(
                    array('sos' => $commentsCollection->getTable('sales_shipment')),
                    'sos.entity_id=main_table.parent_id',
                    array()
                );
                $commentsCollection->getSelect()->where('sos.udpo_id in (?)', array_keys($posToCollect));
                $commentsCollection->getSelect()->columns(array('po_id' => 'sos.udpo_id'));
            } else {
                $commentsCollection->addAttributeToFilter('parent_id', array('in' => array_keys($posToCollect)));
                $commentsCollection->getSelect()->columns(array('po_id' => 'parent_id'));
            }
            $commentsCollection->getSelect()
                ->columns(array('adjustment_prefix_type' => new \Zend_Db_Expr("'shipment_comment'")));
            $comments[] = $commentsCollection;
            foreach ($comments as $_comments) {
                foreach ($_comments as $comment) {
                    if (!preg_match("#({$adjTriggerQ})\\s*([0-9.-]+)\\s*(.*)\$#m", $comment->getComment(), $match)) {
                        continue;
                    }
                    $sId = $comment->getPoId();
                    if (!isset($adjAmounts[$sId])) {
                        $adjAmounts[$sId] = 0;
                        $adjustments[$sId] = array();
                    }
                    $adjKey = $this->getAdjustmentPrefix($comment->getAdjustmentPrefixType()) . $comment->getId();
                    $adjustments[$sId][$adjKey] = array(
                        'adjustment_id' => $adjKey,
                        'po_id' => $posToCollect[$sId]->getIncrementId(),
                        'po_type' => $poType,
                        'amount' => (float)$match[2],
                        'comment' => $match[1] . ' ' . $match[3],
                        'created_at' => $comment->getCreatedAt(),
                        'username' => $comment->getUsername(),
                    );
                    $adjAmounts[$sId] += (float)$match[2];
                }
            }
            foreach ($posToCollect as $sId => $po) {
                if (isset($adjAmounts[$sId])) {
                    $po->setAdjustmentAmount($adjAmounts[$sId]);
                    $po->setAdjustments($adjustments[$sId]);
                } else {
                    $po->setAdjustmentAmount(0);
                    $po->setAdjustments(array());
                }
            }
        }
        return $this;
    }

    public function isStatementAsInvoice()
    {
        return $this->getScopeConfig('udropship/statement/statement_usage') == 'invoice';
    }

    protected $_emptyStatementRefundTotalsAmount = array(
        'subtotal' => 0, 'tax' => 0, 'shipping' => 0, 'discount' => 0, 'hidden_tax' => 0,
        'com_amount' => 0, 'total_refund' => 0, 'refund_payment' => 0, 'refund_invoice' => 0
    );
    protected $_emptyStatementRefundCalcTotalsAmount = array(
        'total_paid' => 0,
        'payment_paid' => 0,
        'invoice_paid' => 0,
    );
    protected $_emptyStatementRefundCalcTotals;
    protected $_emptyStatementRefundTotals;

    protected function _getStatementEmptyRefundTotalsAmount($calc = false, $format = false)
    {
        if (!$calc) {
            $est = &$this->_emptyStatementRefundTotals;
            $esta = &$this->_emptyStatementRefundTotalsAmount;
        } else {
            $est = &$this->_emptyStatementRefundCalcTotals;
            $esta = &$this->_emptyStatementRefundCalcTotalsAmount;
        }
        if ($format && is_null($est)) {
            $this->formatAmounts($est, $esta, true);
        }
        return $format ? $est : $esta;
    }

    public function getStatementEmptyRefundTotalsAmount($format = false)
    {
        return $this->_getStatementEmptyRefundTotalsAmount(false, $format);
    }

    protected $_emptyStatementTotalsAmount = array(
        'subtotal' => 0, 'tax' => 0, 'shipping' => 0, 'handling' => 0, 'discount' => 0, 'hidden_tax' => 0,
        'com_amount' => 0, 'trans_fee' => 0, 'adj_amount' => 0, 'total_payout' => 0, 'total_refund' => 0,
        'refund_invoice' => 0, 'refund_payment' => 0,
        'total_payment' => 0, 'total_invoice' => 0,
    );
    protected $_emptyStatementCalcTotalsAmount = array(
        'total_paid' => 0,
        'invoice_paid' => 0,
        'payment_paid' => 0,
    );
    protected $_emptyStatementCalcTotals;
    protected $_emptyStatementTotals;

    protected function _getStatementEmptyTotalsAmount($calc = false, $format = false)
    {
        if (!$calc) {
            $est = &$this->_emptyStatementTotals;
            $esta = &$this->_emptyStatementTotalsAmount;
        } else {
            $est = &$this->_emptyStatementCalcTotals;
            $esta = &$this->_emptyStatementCalcTotalsAmount;
        }
        if ($format && is_null($est)) {
            $this->formatAmounts($est, $esta, true);
        }
        return $format ? $est : $esta;
    }

    public function getStatementEmptyTotalsAmount($format = false)
    {
        return $this->_getStatementEmptyTotalsAmount(false, $format);
    }

    public function getStatementEmptyCalcTotalsAmount($format = false)
    {
        return $this->_getStatementEmptyTotalsAmount(true, $format);
    }

    public function formatAmounts(&$data, $defaultAmounts = null, $useDefault = false)
    {
        /* @var \Magento\Framework\Pricing\PriceCurrencyInterface $priceHlp */
        $priceHlp = $this->getObj('\Magento\Framework\Pricing\PriceCurrencyInterface');
        $iter = (is_null($defaultAmounts) ? $data : $defaultAmounts);
        if (is_array($iter)) {
            foreach ($iter as $k => $v) {
                if ($useDefault == 'merge' || $useDefault && !isset($data[$k])) {
                    $data[$k] = $priceHlp->format($v, false);
                } elseif (isset($data[$k])) {
                    $data[$k] = $priceHlp->format($data[$k], false);
                }
            }
        }
        return $this;
    }

    public function getStatementEmptyOrderAmounts($format = false)
    {
        return $this->getStatementEmptyTotalsAmount($format);
    }

    public function getPoOrderIncrementId($po)
    {
        return $po->hasOrderIncrementId() ? $po->getOrderIncrementId() : $po->getOrder()->getIncrementId();
    }

    public function getPoOrderCreatedAt($po)
    {
        return $po->hasOrderCreatedAt() ? $po->getOrderCreatedAt() : $po->getOrder()->getCreatedAt();
    }

    public function getItemStockCheckQty($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            if ($item->hasUdpoCreateQty()) {
                return $item->getUdpoCreateQty();
            }
            {
                return $this->isUdpoActive()
                    ? $this->udpoHlp()->getOrderItemQtyToUdpo($item, true)
                    : $item->getQtyOrdered() - $item->getQtyCanceled() - $item->getQtyRefunded();
            }
        } else {
            $parentQty = $item->getParentItem() ? $item->getParentItem()->getQty() : 1;
            return $item->getQty() * $parentQty;
        }
    }

    public function getAddressByItem($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            return $item->getOrder()->getShippingAddress()
                ? $item->getOrder()->getShippingAddress()
                : ($item->getOrder()->getBillingAddress()
                    ? $item->getOrder()->getBillingAddress()
                    : null
                );
        } else {
            $address = $item->getQuote() ? $item->getQuote()->getShippingAddress() : null;
            if ($item->getAddress()) {
                $address = $item->getAddress();
            }
            return $address ? $address : null;
        }
    }

    public function getZipcodeByItem($item)
    {
        $address = $this->getAddressByItem($item);
        return $address ? $address->getPostcode() : null;
    }

    public $returnCountryOnlyWhenHaveZip = true;

    public function getCountryByItem($item)
    {
        $countryId = null;
        $address = $this->getAddressByItem($item);
        if ($address) {
            $countryId = $address->getCountryId();
            if ($this->returnCountryOnlyWhenHaveZip && !$address->getPostcode()) {
                $countryId = null;
            }
        }
        return $countryId;
    }

    public function getItemBaseCost($item, $altCost = null)
    {
        $result = abs($altCost) < 0.001 ? (abs($item->getBaseCost()) < 0.001 ? $item->getBasePrice() : $item->getBaseCost()) : $altCost;
        return abs($altCost) < 0.001 ? (abs($item->getBaseCost()) < 0.001 ? $item->getBasePrice() : $item->getBaseCost()) : $altCost;
    }

    public function getSalesEntityVendors($entity)
    {
        if (!is_callable(array($entity, 'getAllItems'))) return array();
        $products = array();
        foreach ($entity->getAllItems() as $si) {
            $products[$si->getProductId()][] = $si;
        }
        $rowIdField = $this->rowIdField();
        $read = $this->rHlp()->getConnection();
        $attr = $this->_eavConfig->getAttribute('catalog_product', 'udropship_vendor');
        $table = $attr->getBackend()->getTable();
        $rHlp = $this->rHlp();
        $select = $read->select()
            ->from(array('vid'=>$table), array())
            ->join(
                ['pid'=>$rHlp->getTableName('catalog_product_entity')],
                "pid.$rowIdField=vid.$rowIdField",
                []
            )
            ->columns(['pid.entity_id', 'vid.value'])
            ->where('vid.attribute_id=?', $attr->getId())
            ->where('pid.entity_id in (?)', array_keys($products));
        $rows = $read->fetchPairs($select);
        $result = array();
        foreach ($products as $pId => $siArr) {
            foreach ($siArr as $item) {
                if ($this->getScopeConfig('udropship/stock/availability', $entity->getStoreId()) == 'local_if_in_stock') {
                    $result[$item->getId()][$this->getLocalVendorId($entity->getStoreId())] = true;
                }
                $children = $item->getChildrenItems() ? $item->getChildrenItems() : $item->getChildren();
                if (!empty($children)) {
                    foreach ($children as $child) {
                        if ($this->getScopeConfig('udropship/stock/availability', $entity->getStoreId()) == 'local_if_in_stock') {
                            $result[$child->getId()][$this->getLocalVendorId($entity->getStoreId())] = true;
                        }
                        if (!empty($rows[$child->getProductId()])) $result[$item->getId()][$rows[$child->getProductId()]] = true;
                    }
                } else {
                    if (!empty($rows[$item->getProductId()])) $result[$item->getId()][$rows[$item->getProductId()]] = true;
                }
            }
        }
        return $result;
    }

    public function getVendorShipmentStatuses()
    {
        if ($this->getScopeConfig('udropship/vendor/is_restrict_shipment_status')) {
            $shipmentStatuses = $this->getScopeConfig('udropship/vendor/restrict_shipment_status');
            if (!is_array($shipmentStatuses)) {
                $shipmentStatuses = explode(',', $shipmentStatuses);
            }
            return $this->src()->setPath('shipment_statuses')->getOptionLabel($shipmentStatuses);
        } else {
            return $this->src()->setPath('shipment_statuses')->toOptionHash();
        }
    }

    public function getVendorTracksCollection($shipment)
    {
        return $shipment->getTracksCollection()->setOrder('master_tracking_id');
    }

    public function isUdropshipOrder($order)
    {
        if (!$order instanceof \Magento\Sales\Model\Order) return false;
        $oSM = $this->explodeOrderShippingMethod($order);
        $forced = $this->getScopeFlag('carriers/udropship/force_active', $order->getStore());
        return $forced || in_array($oSM[0], array('udropship', 'udsplit'));
    }

    public function getOrderItemById($order, $itemId)
    {
        $orderItem = $order->getItemById($itemId);
        if (!$orderItem) {
            foreach ($order->getItemsCollection() as $item) {
                if ($item->getId() == $itemId) {
                    return $item;
                }
            }
        }
        return $orderItem;
    }

    public function addShipmentComment($shipment, $comment, $visibleToVendor = true, $isVendorNotified = false, $isCustomerNotified = false)
    {
        if (!$comment instanceof \Magento\Sales\Model\Order\Shipment\Comment) {
            $statuses = $this->src()->setPath('shipment_statuses')->toOptionHash();
            $comment = $this->createObj('\Magento\Sales\Model\Order\Shipment\Comment')
                ->setComment($comment)
                ->setIsCustomerNotified($isCustomerNotified)
                ->setIsVendorNotified($isVendorNotified)
                ->setIsVisibleToVendor($visibleToVendor)
                ->setUdropshipStatus(@$statuses[$shipment->getUdropshipStatus()]);
        }
        $shipment->addComment($comment);
        return $this;
    }

    public function processShipmentStatusSave($shipment, $status)
    {
        if ($shipment->getUdropshipStatus() != $status) {
            $oldStatus = $shipment->getUdropshipStatus();
            $this->_eventManager->dispatch(
                'udropship_shipment_status_save_before',
                array('shipment' => $shipment, 'old_status' => $oldStatus, 'new_status' => $status)
            );
            $comment = sprintf("[Shipment status changed from '%s' to '%s']",
                $this->getShipmentStatusName($shipment->getUdropshipStatus()),
                $this->getShipmentStatusName($status)
            );
            $shipment->setUdropshipStatus($status);
            $shipment->getResource()->saveAttribute($shipment, 'udropship_status');
            $this->addShipmentComment($shipment, $comment);
            $shipment->getCommentsCollection()->save();
            $this->_eventManager->dispatch(
                'udropship_shipment_status_save_after',
                ['shipment' => $shipment, 'old_status' => $oldStatus, 'new_status' => $status, 'object'=>$shipment]
            );
        }
        return $this;
    }

    public function processPoStatusSave($po, $status)
    {
        if ($po instanceof \Unirgy\DropshipPo\Model\Po) {
            $this->udpoHlp()->processPoStatusSave($po, $status, true);
        } elseif ($po instanceof \Unirgy\DropshipStockPo\Model\Po) {
            $this->ustockpoHlp()->processPoStatusSave($po, $status, true);
        } else {
            $this->processShipmentStatusSave($po, $status);
        }
        return $this;
    }

    function array_merge_2(&$array, &$array_i)
    {
        // For each element of the array (key => value):
        foreach ($array_i as $k => $v) {
            // If the value itself is an array, the process repeats recursively:
            if (is_array($v)) {
                if (!isset($array[$k])) {
                    $array[$k] = array();
                }
                $this->array_merge_2($array[$k], $v);

                // Else, the value is assigned to the current element of the resulting array:
            } else {
                if (isset($array[$k]) && is_array($array[$k])) {
                    $array[$k][0] = $v;
                } else {
                    if (isset($array) && !is_array($array)) {
                        $temp = $array;
                        $array = array();
                        $array[0] = $temp;
                    }
                    $array[$k] = $v;
                }
            }
        }
    }


    public function array_merge_n()
    {
        // Initialization of the resulting array:
        $array = array();

        // Arrays to be merged (function's arguments):
        @$arrays =& \func_get_args();

        // Merging of each array with the resulting one:
        foreach ($arrays as $array_i) {
            if (is_array($array_i)) {
                $this->array_merge_2($array, $array_i);
            }
        }

        return $array;
    }

    public function isUrlKeyReserved($urlKey)
    {
        /** @var \Magento\Framework\App\Route\Config $routeConfig */
        $routeConfig = $this->getObj('\Magento\Framework\App\Route\Config');
        return $routeConfig->getRouteByFrontName($urlKey);
    }

    public function getRouteFrontName($routeId)
    {
        /** @var \Magento\Framework\App\Route\Config $routeConfig */
        $routeConfig = $this->getObj('\Magento\Framework\App\Route\Config');
        return $routeConfig->getRouteFrontName($routeId);
    }

    public function hasExtraChargeMethod($vendor, $vMethod)
    {
        return $vendor->getAllowShippingExtraCharge() && @$vMethod['allow_extra_charge'];
    }

    public function getExtraChargeData($vendor, $vMethod, $field)
    {
        return null !== @$vMethod[$field] ? $vMethod[$field] : $vendor->getData('default_shipping_' . $field);
    }

    public function getExtraChargeRate($request, $rate, $vendor, $vMethod)
    {
        $vendor = $this->getVendor($vendor);
        if ($this->hasExtraChargeMethod($vendor, $vMethod)) {
            $exRate = clone $rate;
            $fields = array();
            foreach (array(
                         'extra_charge_suffix', 'extra_charge_type', 'extra_charge'
                     ) as $field) {
                $fields[$field] = $this->getExtraChargeData($vendor, $vMethod, $field);
            }
            $exRate->setSuffix(' ' . $fields['extra_charge_suffix']);
            $exRate->setMethod($exRate->getMethod() . '___ext');
            $exRate->setMethodTitle($exRate->getMethodTitle() . ' ' . $fields['extra_charge_suffix']);
            switch ($fields['extra_charge_type']) {
                case 'shipping_percent':
                    $exPrice = $exRate->getPrice() * abs($fields['extra_charge']) / 100;
                    break;
                case 'subtotal_percent':
                    $exPrice = $request->getPackageValue() * abs($fields['extra_charge']) / 100;
                    break;
                case 'fixed':
                    $exPrice = abs($fields['extra_charge']);
                    break;
            }
            $exRate->setBeforeExtPrice($exRate->getPrice());
            $exRate->setPrice($exRate->getPrice() + $exPrice);
            $exRate->setIsExtraCharge(true);
            $rate->setHasExtraCharge(true);
            $exRate->setHasExtraCharge(true);
            return $exRate;
        }
        return false;
    }

    public function processDateLocaleToInternal(&$data, $dateFields, $format = null, $recalc = false)
    {
        foreach ($dateFields as $dateField) {
            if (is_array($data)) {
                if (!empty($data[$dateField])) {
                    $data[$dateField] = $this->dateLocaleToInternal(
                        $data[$dateField], $format, $recalc
                    );
                }
            } elseif ($data instanceof DataObject) {
                if ($data->getData($dateField)) {
                    $data->setData($dateField, $this->dateLocaleToInternal(
                        $data->getData($dateField), $format, $recalc
                    ));
                }
            }
        }
        return $this;
    }

    public function dateLocaleToInternal($date, $format = null, $recalc = false)
    {
        try {
            $result = $this->_dateLocaleToInternal($date, $format, $recalc);
        } catch (\Exception $e) {
            $result = $date;
        }
        return $result;
    }

    protected function _dateLocaleToInternal($date, $format = null, $recalc = false)
    {
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $locale = $this->getObj('\Magento\Framework\Locale\Resolver')->getLocale();
        $timezone = $this->getScopeConfig($localeDate->getDefaultTimezonePath());
        $defaultTimezone = $localeDate->getDefaultTimezone();
        $defaultFormat = \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT;
        $formatter = new \IntlDateFormatter($locale, null, null);
        if (is_null($format)) {
            $format = \IntlDateFormatter::SHORT;
        }
        if (is_numeric($format)) {
            $format = $localeDate->getDateFormat($format);
        }
        if ($date instanceof \IntlCalendar || $date instanceof \DateTime) {
            $date = $formatter->formatObject($date, $format);
        }
        $formatter->setPattern($format);
        $formatter->setTimeZone($timezone);
        $timestamp = $formatter->parse($date);
        $formatter->setPattern($defaultFormat);
        if ($recalc) {
            $formatter->setTimeZone($defaultTimezone);
        }
        return $formatter->format($timestamp);
    }

    public function processDateInternalToLocale(&$data, $dateFields, $format = null, $recalc = false)
    {
        foreach ($dateFields as $dateField) {
            if (is_array($data)) {
                if (!empty($data[$dateField])) {
                    $data[$dateField] = $this->dateInternalToLocale(
                        $data[$dateField], $format, $recalc
                    );
                }
            } elseif ($data instanceof DataObject) {
                if ($data->getData($dateField)) {
                    $data->setData($dateField, $this->dateInternalToLocale(
                        $data->getData($dateField), $format, $recalc
                    ));
                }
            }
        }
        return $this;
    }

    public function dateInternalToLocale($date, $format = null, $recalc = false, $hasTime = true)
    {
        try {
            $result = $this->_dateInternalToLocale($date, $format, $recalc, $hasTime);
        } catch (\Exception $e) {
            $result = $date;
        }
        return $result;
    }

    protected function _dateInternalToLocale($date, $format = null, $recalc = false, $hasTime = true)
    {
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $locale = $this->getObj('\Magento\Framework\Locale\Resolver')->getLocale();
        $timezone = $this->getScopeConfig($localeDate->getDefaultTimezonePath());
        $defaultTimezone = $localeDate->getDefaultTimezone();
        $defaultFormat = \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT;
        if (!$hasTime) {
            $defaultFormat = \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT;
        }
        $formatter = new \IntlDateFormatter($locale, null, null);
        if (is_null($format)) {
            $format = \IntlDateFormatter::SHORT;
        }
        if (is_numeric($format)) {
            $format = $localeDate->getDateFormat($format);
        }
        if ($date instanceof \IntlCalendar || $date instanceof \DateTime) {
            $date = $formatter->formatObject($date, $defaultFormat);
        }
        $formatter->setPattern($defaultFormat);
        $formatter->setTimeZone($defaultTimezone);
        $timestamp = $formatter->parse($date);
        $formatter->setPattern($format);
        if ($recalc) {
            $formatter->setTimeZone($timezone);
        }
        return $formatter->format($timestamp);
    }

    public function mapSystemToUdropshipMethod($code, $vendor)
    {
        $vendor = $this->getVendor($vendor);
        $systemMethods = $this->getShippingMethods();
        $vendorMethods = $vendor->getShippingMethods();
        $found = false;
        foreach ($vendorMethods as $__vendorMethod) {
            foreach ($__vendorMethod as $vendorMethod) {
                if ($code == $vendorMethod['carrier_code'] . '_' . $vendorMethod['method_code']) {
                    $found = $vendorMethod['shipping_id'];
                    break;
                }
            }
        }
        if (!$found) {
            foreach ($systemMethods as $systemMethod) {
                $systemMethod->useProfile($vendor);
                $__sysMethods = $systemMethod->getSystemMethods();
                if ($__sysMethods) {
                    foreach ($__sysMethods as $sc => $__sm) {
                        if (!is_array($__sm)) {
                            $__sm = array($__sm => $__sm);
                        }
                        foreach ($__sm as $sm) {
                            if ($code == $sc . '_' . $sm
                                || $sm == '*' && 0 === strpos($code, $sc . '_')
                            ) {
                                $found = $systemMethod->getId();
                                break;
                            }
                        }
                    }
                }
                $systemMethod->resetProfile();
            }
        }
        static $unknown;
        if (null === $unknown) {
            /* @var \Unirgy\Dropship\Model\Shipping $unknown */
            $unknown = $this->createObj('\Unirgy\Dropship\Model\Shipping');
            $unknown->setData(array(
                'shipping_code' => '***unknown***',
                'shipping_title' => '***Unknown***',
            ));
        }
        return $found && $systemMethods->getItemById($found) ? $systemMethods->getItemById($found) : $unknown;
    }

    public function formatCustomerAddress($address, $format, $vendor)
    {
        $vendor = $this->getVendor($vendor);
        $address->setData('__udropship_vendor', $vendor);
        if ($address instanceof \Magento\Sales\Model\Order\Address) {
            /** @var \Magento\Sales\Model\Order\Address\Renderer $addressRenderer */
            $addressRenderer = $this->getObj('\Magento\Sales\Model\Order\Address\Renderer');
            $result = $addressRenderer->format($address, $format);
        } else {
            $result = $address->format($format);
        }
        $address->unsetData('__udropship_vendor');
        return $result;
    }

    public function getResizedVendorLogoUrl($v, $width, $height, $field = 'logo')
    {
        $v = $this->getVendor($v);
        $subdir = 'vendor' . '/' . $v->getId();
        return $this->getResizedImageUrl($v->getData($field), $width, $height, $subdir);
    }

    public function getResizedImageUrl($file, $width, $height, $subdir = 'vendor')
    {
        if (!$file) {
            return '**EMPTY**';
        }
        try {
            /* @var \Unirgy\Dropship\Model\ProductImage $model */
            $model = $this->createObj('\Unirgy\Dropship\Model\ProductImage')
                ->setDestinationSubdir($subdir)
                ->setWidth($width)
                ->setHeight($height)
                ->setBaseFile($file);
            if (!$model->isCached()) {
                $model->resize()->saveFile();
            }
            return $model->getUrl();
        } catch (\Exception $e) {
            $this->_logger->error($e);
            return false;
        }
    }

    public function serialize($value)
    {
        return \Zend_Json::encode($value);
    }

    public function unserialize($value)
    {
        if (empty($value)) {
            $value = empty($value) ? array() : $value;
        } elseif (!is_array($value)) {
            if (strpos($value, 'a:') === 0) {
                $value = @unserialize($value);
                if (!is_array($value)) {
                    $value = array();
                }
            } elseif (strpos($value, '{') === 0 || strpos($value, '[{') === 0) {
                try {
                    $value = \Zend_Json::decode($value);
                } catch (\Exception $e) {
                    $value = array();
                }
            } elseif ($value == '[]') {
                $value = array();
            }
        }
        return $value;
    }

    public function unserializeArr($value)
    {
        $value = $this->unserialize($value);
        if (!is_array($value)) {
            $value = array();
        }
        return $value;
    }

    public function isZipcodeMatch($zipCode, $limitZipcode)
    {
        if (trim($zipCode) == '') return true;
        $zipCodes = $limitZipcode;
        $zipCodes = preg_replace('/(\s*[,;]\s*)+/', "\n", $zipCodes);
        $zipCodes = preg_replace('/\s*-\s*/', '-', $zipCodes);
        $zipCodes = array_map('trim', explode("\n", $zipCodes));
        $result = true;
        $zipCode = strtolower($zipCode);
        foreach ($zipCodes as $zc) {
            $zc = strtolower($zc);
            if (($zcGlog = preg_split('/(' . implode('|', array_map('preg_quote', array('?', '*', '+', '.'))) . ')/', $zc, -1, PREG_SPLIT_DELIM_CAPTURE))
                && count($zcGlog) > 1
            ) {
                $result = false;
                $zcReg = '/^';
                foreach ($zcGlog as $zcSub) {
                    if (in_array($zcSub, array('?', '*', '+'))) {
                        $zcReg .= '.' . $zcSub;
                    } elseif ($zcSub == '.') {
                        $zcReg .= $zcSub;
                    } else {
                        $zcReg .= preg_quote($zcSub, '/');
                    }
                }
                if (preg_match($zcReg . '$/', trim($zipCode))) return true;
            } elseif (strpos($zc, '-')) {
                $result = false;
                list($zcFrom, $zcTo) = explode('-', $zc, 2);
                if (trim($zcFrom) <= trim($zipCode) && trim($zipCode) <= trim($zcTo)) return true;
            } elseif (trim($zc) != '') {
                $result = false;
                if (trim($zc) == trim($zipCode)) return true;
            }
        }
        return $result;
    }

    public function getOrderObj($dataObject)
    {
        $order = false;
        if ($dataObject instanceof \Magento\Sales\Model\Order) {
            $order = $dataObject;
        } else {
            $order = $dataObject->getOrder();
        }
        return $order;
    }

    public function orderToBaseRate($order, $amount = false, $round = false)
    {
        $_orderRate = $this->baseToOrderRate($order);
        $_orderRateRev = 1 / $_orderRate;
        return $amount !== false
            ? ($round ? $this->roundPrice($amount * $_orderRateRev) : $amount * $_orderRateRev)
            : $_orderRateRev;
    }

    public function baseToOrderRate($order, $baseAmount = false, $round = false)
    {
        $order = $this->getOrderObj($order);
        $_orderRate = $order->getBaseToOrderRate() > 0 ? $order->getBaseToOrderRate() : 1;
        return $baseAmount !== false
            ? ($round ? $this->roundPrice($baseAmount * $_orderRate) : $baseAmount * $_orderRate)
            : $_orderRate;
    }

    public function displayPrices($dataObject, $basePrice = false, $price = false, $strong = false, $separator = '<br/>')
    {
        if ($basePrice === false && $price === false) {
            throw new \Exception('Both prices cannot be false');
        }
        if ($basePrice === false) {
            $basePrice = $this->orderToBaseRate($dataObject, $price);
        } else if ($price === false) {
            $price = $this->baseToOrderRate($dataObject, $basePrice);
        }
        return $this->getObj('\Magento\Sales\Helper\Admin')->displayPrices($dataObject, $basePrice, $price, $strong, $separator);
    }

    public function checkProductCollectionAttribute($attrCode)
    {
        return ($attr = $this->_eavConfig->getCollectionAttribute(Product::ENTITY, $attrCode))
        && $attr->getAttributeId();
    }

    public function checkProductAttribute($attrCode)
    {
        return ($attr = $this->_eavConfig->getAttribute(Product::ENTITY, $attrCode))
        && $attr->getAttributeId();
    }

    public function getProductAttribute($attrCode)
    {
        return (($attr = $this->_eavConfig->getAttribute(Product::ENTITY, $attrCode))
            && $attr->getAttributeId()
        )
            ? $attr : false;
    }

    public function getVendorFallbackField($vendor, $field, $configPath)
    {
        $vendor = $this->getVendor($vendor);
        if ($vendor->getData($field) == -1) {
            return $this->scopeConfig->getValue($configPath);
        } else {
            return $vendor->getData($field);
        }
    }

    public function getVendorFallbackFlagField($vendor, $field, $configPath)
    {
        $vendor = $this->getVendor($vendor);
        if ($vendor->getData($field) == -1) {
            return $this->scopeConfig->isSetFlag($configPath);
        } else {
            return $vendor->getData($field);
        }
    }

    public function getVendorUseCustomFallbackField($vendor, $useCustomField, $field, $configPath)
    {
        $vendor = $this->getVendor($vendor);
        if (!$vendor->getData($useCustomField)) {
            return $this->scopeConfig->getValue($configPath);
        } else {
            return $vendor->getData($field);
        }
    }

    public function getVendorUseCustomFallbackFlagField($vendor, $useCustomField, $field, $configPath)
    {
        $vendor = $this->getVendor($vendor);
        if (!$vendor->getData($useCustomField)) {
            return $this->scopeConfig->isSetFlag($configPath);
        } else {
            return $vendor->getData($field);
        }
    }

    public function isSeparateShipment($orderItem, $vendor = null)
    {
        if ($vendor === null) {
            if ($orderItem->hasUdpoUdropshipVendor()) {
                $vendor = $orderItem->getUdpoUdropshipVendor();
            } else {
                $vendor = $orderItem->getUdropshipVendor();
            }
        }
        $vendor = $this->getVendor($vendor);
        $result = $this->getVendorFallbackFlagField(
            $vendor, 'create_per_item_shipment', 'udropship/misc/create_per_item_shipment'
        );
        if (-1 != $orderItem->getUdsepoShipmentType()
            && $orderItem->hasData('udsepo_shipment_type')
        ) {
            $result = $orderItem->getUdsepoShipmentType();
        }
        $oiParent = $orderItem->getParentItem();
        if (!$result && $oiParent) {
            $result = $oiParent->getUdsepoShipmentType() == 2;
        }
        return $result;
    }

    public function isSeparatePo($orderItem, $vendor = null)
    {
        if ($vendor === null) {
            $vendor = $orderItem->getUdropshipVendor();
        }
        $vendor = $this->getVendor($vendor);
        $result = $this->getVendorFallbackFlagField(
            $vendor, 'create_per_item_po', 'udropship/misc/create_per_item_po'
        );
        if (-1 != $orderItem->getUdsepoPoType()
            && $orderItem->hasData('udsepo_po_type')
        ) {
            $result = $orderItem->getUdsepoPoType();
        }
        $oiParent = $orderItem->getParentItem();
        if (!$result && $oiParent) {
            $result = $oiParent->getUdsepoPoType() == 2;
        }
        return $result;
    }

    public function isShowVendorSkuColumnInStockTab()
    {
        return $this->getScopeFlag('udropship/stock/show_vendor_sku_column');
    }

    public function isShowVendorSkuColumnInProductsTab()
    {
        return $this->getScopeFlag('udprod/general/show_vendor_sku_column');
    }

    public function getVendorPortalCustomUrl()
    {
        return $this->isModuleActive('Unirgy_DropshipVendorPortalUrl')
            ? $this->getScopeConfig('udropship/admin/vendor_portal_url')
            : false;
    }

    public function isStatementRefundsEnabled()
    {
        return $this->getScopeConfig('udropship/statement/enable_refunds');
    }

    public function getShippingPrice($baseShipping, $vId, $address, $type)
    {
        $hlp = $this;
        $isUdtax = $hlp->isModuleActive('Unirgy_DropshipVendorTax');
        $calc = $this->getObj('\Magento\Tax\Model\Calculation');
        /** @var \Magento\Tax\Model\Config $config */
        $config = $this->getObj('\Magento\Tax\Model\Config');

        if ($vId instanceof Vendor) {
            $vId = $vId->getId();
        }

        $store = $address->getQuote()->getStore();
        $storeTaxRequest = $calc->getRateRequest(false, false, false, $store);
        $addressTaxRequest = $calc->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $store
        );

        $priceIncludesTax = $config->shippingPriceIncludesTax($store);

        $shippingTaxClass = $config->getShippingTaxClass($store);
        $storeTaxRequest->setProductClassId($shippingTaxClass);
        $addressTaxRequest->setProductClassId($shippingTaxClass);

        if ($isUdtax) {
            $this->udtaxHlp()->setRequestVendorClassId($storeTaxRequest, $vId);
            $this->udtaxHlp()->setRequestVendorClassId($addressTaxRequest, $vId);
        }

        $rate = $calc->getRate($addressTaxRequest);

        if ($priceIncludesTax) {
            $storeRate = $calc->getStoreRate($addressTaxRequest, $store);
            $baseStoreTax = $calc->calcTaxAmount($baseShipping, $storeRate, true, false);
            $baseShipping = $calc->round($baseShipping - $baseStoreTax);
            $baseTax = $calc->round($calc->calcTaxAmount($baseShipping, $rate, false, false));
            $baseTaxShipping = $baseShipping + $baseTax;
        } else {
            $baseTax = $calc->round($calc->calcTaxAmount($baseShipping, $rate, false, false));
            $baseTaxShipping = $baseShipping + $baseTax;
        }

        $result = $baseShipping;
        if ($type == 'tax') {
            $result = $baseTax;
        } elseif ($type == 'incl') {
            $result = $baseTaxShipping;
        }
        return $result;
    }

    public function jsonEncode($valueToEncode)
    {
        /* @var \Magento\Framework\Json\Helper\Data $jsonHlp */
        $jsonHlp = $this->getObj('\Magento\Framework\Json\Helper\Data');
        return $jsonHlp->jsonEncode($valueToEncode);
    }

    public function jsonDecode($encodedValue)
    {
        /* @var \Magento\Framework\Json\Helper\Data $jsonHlp */
        $jsonHlp = $this->getObj('\Magento\Framework\Json\Helper\Data');
        return $jsonHlp->jsonDecode($encodedValue);
    }

    public function urlDecode($url)
    {
        return $this->urlDecoder->decode($url);
    }

    public function urlEncode($url)
    {
        return $this->urlEncoder->encode($url);
    }

    public function decorateArray($array, $prefix = 'decorated_', $forceSetAll = false)
    {
        return $this->getObj('\Magento\Framework\Stdlib\ArrayUtils')->decorateArray($array, $prefix, $forceSetAll);
    }

    public function isScopeDateInInterval($scope, $dateFrom = null, $dateTo = null)
    {
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        return $localeDate->isScopeDateInInterval($scope, $dateFrom, $dateTo);
    }

    public function getDateFormatWithLongYear()
    {
        /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate */
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        return $localeDate->getDateFormatWithLongYear();
    }

    public function getDateFormat($format = \IntlDateFormatter::SHORT)
    {
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        return $localeDate->getDateFormat($format);
    }
    public function getDefaultDateFormat($format = \IntlDateFormatter::SHORT)
    {
        $result = (new \IntlDateFormatter(
            'en_US',
            $format,
            \IntlDateFormatter::NONE
        ))->getPattern();
        return $result;
    }

    public function formatDate($date = null, $format = \IntlDateFormatter::SHORT, $showTime = false)
    {
        /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate */
        $localeDate = $this->getObj('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        return $localeDate->formatDate($date, $format, $showTime);
    }

    public function formatNumber($value)
    {
        return $this->getObj('\Magento\Framework\Locale\FormatInterface')->getNumber($value);
    }

    public function formatPrice($amount,
                                $includeContainer = true,
                                $precision = \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                                $scope = null,
                                $currency = null
    )
    {
        /* @var \Magento\Framework\Pricing\PriceCurrencyInterface $priceHlp */
        $priceHlp = $this->getObj('\Magento\Framework\Pricing\PriceCurrencyInterface');
        return $priceHlp->format($amount, $includeContainer, $precision, $scope, $currency);
    }

    public function roundPrice($amount)
    {
        /* @var \Magento\Framework\Pricing\PriceCurrencyInterface $priceHlp */
        $priceHlp = $this->getObj('\Magento\Framework\Pricing\PriceCurrencyInterface');
        return $priceHlp->round($amount);
    }

    public function disableJrdEmptyCatEvent()
    {
        if ($this->isModuleActive('JRD_DisableEmptyCategories')) {
            Mage::getConfig()->setNode('frontend/events/catalog_category_collection_load_after/observers/disable_empty_categories/class', 'udropship/observer');
            Mage::getConfig()->setNode('frontend/events/catalog_category_collection_load_after/observers/disable_empty_categories/method', 'dummy');
        }
    }

    public function now()
    {
        return (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @return \Unirgy\Dropship\Model\Config
     */
    public function config()
    {
        return $this->getObj('\Unirgy\Dropship\Model\Config');
    }

    /**
     * @return \Unirgy\Dropship\Model\Source
     */
    public function src()
    {
        return $this->getObj('\Unirgy\Dropship\Model\Source');
    }

    /**
     * @return \Unirgy\Dropship\Model\ResourceModel\Helper
     */
    public function rHlp()
    {
        return $this->getObj('\Unirgy\Dropship\Model\ResourceModel\Helper');
    }

    /**
     * @return \Unirgy\Dropship\Model\Session
     */
    public function session()
    {
        return $this->getObj('\Unirgy\Dropship\Model\Session');
    }

    public function getDefaultStoreView()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    public function getVendorPortalJsBaseUrl()
    {
        $store = $this->getDefaultStoreView();
        if ($this->_registry->registry('uvp_url_store')) {
            $store = $this->_registry->registry('uvp_url_store');
        }
        $jsBaseUrl = $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_WEB]);
        $vendorPortalUrl = $this->getVendorPortalCustomUrl();
        if ($vendorPortalUrl) {
            $jsBaseUrl = $vendorPortalUrl;
        }
        $jsBaseUrl = rtrim($jsBaseUrl, '/') . '/js/';
        return $jsBaseUrl;
    }

    public function getVendorPortalJsUrl($url, $params = array())
    {
        $store = $this->getDefaultStoreView();
        if ($this->_registry->registry('uvp_url_store')) {
            $store = $this->_registry->registry('uvp_url_store');
        }
        $params['_store'] = $store;
        return $this->_getUrl($url, $params);
    }

    public function sortBySortOrder($a, $b)
    {
        if (@$a['sort_order'] < @$b['sort_order']) {
            return -1;
        } elseif (@$a['sort_order'] > @$b['sort_order']) {
            return 1;
        }
        return 0;
    }

    protected $_skipQuoteLoadAfterEvent = array();

    public function isSkipQuoteLoadAfterEvent($qId, $flag = null)
    {
        $oldFlag = !empty($this->_skipQuoteLoadAfterEvent[$qId]);
        if ($flag !== null) $this->_skipQuoteLoadAfterEvent[$qId] = $flag;
        return $oldFlag;
    }

    public function getAdminhtmlFrontName()
    {
        return Mage::app()->getFrontController()->getRouterByRoute('adminhtml')->getFrontNameByRoute('adminhtml');
    }

    public function createObj($class, array $arguments = [])
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->create($class, $arguments);
    }

    public function getObj($class)
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get($class);
    }

    public function getScopeConfig($path, $scopeCode = null, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        if ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
            $scopeCode = $scopeCode->getCode();
        }
        return $this->scopePool->getScope($scopeType, $scopeCode)->getValue($path);
    }

    public function getScopeFlag($path, $scopeCode = null, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        if ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
            $scopeCode = $scopeCode->getCode();
        }
        return !!$this->scopePool->getScope($scopeType, $scopeCode)->getValue($path);
    }

    /**
     * @return \Unirgy\Dropship\Plugin\Config
     */
    public function configPlugin()
    {
        return $this->getObj('\Unirgy\Dropship\Plugin\Config');
    }

    public function setScopeConfig($path, $value, $scopeCode = null, $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        if ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
            $scopeCode = $scopeCode->getCode();
        }
        $this->scopePool->getScope($scopeType, $scopeCode)->setValue($path, $value);
        $this->configPlugin()->setModifiedConfig($value, $path, $scopeType, $scopeCode);
        return $this;
    }
    public function hasModifiedConfig($path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->configPlugin()->hasModifiedConfig($path, $scope, $scopeCode);
    }
    public function getModifiedConfig($path = null, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->configPlugin()->getModifiedConfig($path, $scope, $scopeCode);
    }

    /**
     * @param $product
     * @return \Magento\CatalogInventory\Model\Stock\Item
     */
    public function getStockItem($product)
    {
        $stockItem = $pId = false;
        $stockRegistry = $this->getObj('\Magento\CatalogInventory\Api\StockRegistryInterface');
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $stockItem = $stockRegistry->getStockItem($product->getId());
        } elseif ($product instanceof \Magento\Framework\DataObject) {
            $stockItem = $stockRegistry->getStockItem($product->getProductId());
        } elseif (is_scalar($product)) {
            $stockItem = $stockRegistry->getStockItem($product);
        }
        return $stockItem;
    }

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    public function transactionFactory()
    {
        if ($this->_transactionFactory === null) {
            $this->_transactionFactory = $this->_objMng->get('\Magento\Framework\DB\TransactionFactory');
        }
        return $this->_transactionFactory;
    }

    protected $_udpoFactory;

    public function udpoFactory()
    {
        if ($this->_udpoFactory === null) {
            $this->_udpoFactory = $this->_objMng->get('\Unirgy\DropshipPo\Model\PoFactory');
        }
        return $this->_udpoFactory;
    }

    protected $_udpoItemFactory;

    public function udpoItemFactory()
    {
        if ($this->_udpoItemFactory === null) {
            $this->_udpoItemFactory = $this->_objMng->get('\Unirgy\DropshipPo\Model\Po\ItemFactory');
        }
        return $this->_udpoItemFactory;
    }

    protected $_shipmentItemFactory;

    public function shipmentItemFactory()
    {
        if ($this->_shipmentItemFactory === null) {
            $this->_shipmentItemFactory = $this->_objMng->get('\Magento\Sales\Model\Order\Shipment\ItemFactory');
        }
        return $this->_shipmentItemFactory;
    }

    function logError($message)
    {
        $this->_logger->error($message);
        return $this;
    }

    function filterObjectsInDump($data, $simple = false)
    {
        $result = '';
        if (is_array($data) || $data instanceof \Magento\Framework\Data\Collection) {
            $result = array();
            foreach ($data as $k => $v) {
                if ($v instanceof \Magento\Framework\DataObject) {
                    $_v = filterObjectsInDump($v->getData());
                    array_unshift($_v, spl_object_hash($v));
                    array_unshift($_v, get_class($v));
                } elseif ($v instanceof \Magento\Framework\Data\Collection) {
                    $_v = filterObjectsInDump($v);
                } elseif (is_array($v)) {
                    $_v = filterObjectsInDump($v);
                } elseif (is_object($v)) {
                    if (method_exists($v, '__toString')) {
                        $_v = get_class($v) . " - " . spl_object_hash($v);
                        if (!$simple) $_v .= "\n\n" . $v;
                    } else {
                        $_v = get_class($v) . " - " . spl_object_hash($v);
                        //if (!$simple) $_v .= var_export($v,1);
                    }
                } else {
                    $_v = $v;
                }
                $result[$k] = $_v;
            }
            if ($data instanceof \Magento\Framework\Data\Collection\AbstractDb) {
                array_unshift($result, $data->getSelect() . '');
            }
            if ($data instanceof \Magento\Framework\Data\Collection) {
                array_unshift($result, spl_object_hash($data));
                array_unshift($result, get_class($data));
            }
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $result = filterObjectsInDump($data->getData());
            array_unshift($result, spl_object_hash($data));
            array_unshift($result, get_class($data));
        } elseif (is_object($data)) {
            if (method_exists($data, '__toString')) {
                $result = get_class($data) . " - " . spl_object_hash($data);
                if (!$simple) $result .= "\n\n" . $data;
            } else {
                $result = get_class($data) . " - " . spl_object_hash($data);
                if (!$simple) $result .= var_export($data, 1);
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    static protected $_dtlIps = array('127.0.0.1', '193.151.57.254');

    public function dump($data, $file, $simple = false)
    {
        if (!in_array(@$_SERVER['REMOTE_ADDR'], self::$_dtlIps) && !in_array(@$_SERVER['HTTP_X_FORWARDED_FOR'], self::$_dtlIps)) return;
        ob_start();
        $filtered = $this->filterObjectsInDump($data, $simple);
        is_array($filtered) ? print_r($filtered) : var_dump($filtered);
        //$this->_logLoggerInterface->log(ob_get_clean(), null, $file);
        file_put_contents(realpath($this->_dirList->getPath('var')) . '/' . 'log' . '/' . $file, ob_get_clean(), FILE_APPEND);
    }

}
}

namespace {
if (!function_exists('udDump')) {
    function udDump($data, $file)
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data')->dump($data, $file, false);
    }
}
if (!function_exists('udLog')) {
    function udLog($data, $file)
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data')->dump($data, $file, true);
    }
}
}
