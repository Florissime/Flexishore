<?php

namespace Unirgy\Dropship\Model\Stock;

use \Magento\Catalog\Model\Product;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\DataObject;
use \Magento\Framework\Registry;
use \Magento\Framework\View\DesignInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Item;
use \Unirgy\Dropship\Model\Vendor;

class Availability extends DataObject
{
    /**
     * @var Item
     */
    protected $_iHlp;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Registry
     */
    protected $_registry;

    protected $_stockRegistry;
    protected $_stockState;

    public function __construct(
        Item $helperItem,
        HelperData $helper,
        ScopeConfigInterface $scopeConfig,
        DesignInterface $viewDesign,
        RequestInterface $request,
        Registry $registry,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        array $data = []
    )
    {
        $this->_iHlp = $helperItem;
        $this->_hlp = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->_viewDesign = $viewDesign;
        $this->_request = $request;
        $this->_registry = $registry;
        $this->_stockRegistry = $stockRegistry;
        $this->_stockState = $stockState;

        parent::__construct($data);
    }

    public function alwaysAssigned($items)
    {
        // needed only for external stock check
        $this->collectStockLevels($items);
        $this->addStockErrorMessages($items, $this->getStockResult());
    }

    public function localIfInStock($items)
    {
        $iHlp = $this->_iHlp;
        $this->collectStockLevels($items, array('request_local'=>true));

        $localVendorId = $this->_hlp->getLocalVendorId();
        foreach ($items as $item) {
            if ($item->getUdropshipVendor()==$localVendorId) {
                continue;
            }
            $stock = $item->getUdropshipStockLevels();
            if (!empty($stock[$localVendorId]['status'])) {
                $iHlp->setUdropshipVendor($item, $localVendorId);
            }
        }
        $this->addStockErrorMessages($items, $this->getStockResult());
    }

    /**
    * Retrieve configuration flag whether to ship from local when in stock
    *
    * @param mixed $store
    * @param int|Vendor
    * @return boolean
    */
    public function getUseLocalStockIfAvailable($store=null, $vendor=null)
    {
        // if vendor is supplied
        if (!is_null($vendor)) {
            // get vendor object
            if (is_numeric($vendor)) {
                $vendor = $this->_hlp->getVendor($vendor);
            }
            $result = $vendor->getUseLocalStock();
            // if there's vendor specific configuration, use it
            if (!is_null($result) && $result!==-1) {
                return $result;
            }
        }
        // otherwise return store configuration value
        return $this->_hlp->getScopeConfig('udropship/stock/availability', $store)=='local_if_in_stock';
    }

    /**
    * Should we get the real inventory status or augmented by local stock?
    *
    * @return boolean
    */
    public function getTrueStock()
    {
        $area = $this->_viewDesign->getArea();
        $controller = $this->_request->getControllerName();

        // when creating order in admin, always use the true stock status
        if (!$this->_registry->registry('inApplyStockAvailability') && $area=='adminhtml' && !in_array($controller, array('sales_order_edit','sales_order_create'))) {
            return true;
        }
        // alwyas use trueStock if configuration says so
        if (!$this->getData('true_stock') && !$this->getUseLocalStockIfAvailable()) {
            $this->setTrueStock(true);
        }

        return $this->getData('true_stock');
    }

    public function collectStockLevels($items, $options=array())
    {
        $hlp = $this->_hlp;
        $iHlp = $this->_iHlp;
        // get $quote and $order objects
        foreach ($items as $item) {
            if (empty($quote)) {
                $quote = $item->getQuote();
                $order = $item->getOrder();
                break;
            }
        }
        if (empty($quote) && empty($order)) {
            $this->setStockResult(array());
            return $this;
        }
        $store = $quote ? $quote->getStore() : $order->getStore();
        $localVendorId = $this->_hlp->getLocalVendorId($store);

        $requests = array();
        foreach ($items as $item) {
            //if ($iHlp->isVirtual($item)) continue;
            if ($item->getHasChildren() || $item->isDeleted()) {
                //$product->getTypeId()=='bundle' || $product->getTypeId()=='configurable') {
                continue;
            }
            $product = $item->getProduct();
            $pId = $item->getProductId();
            if (!$product || !$product->hasUdropshipVendor()) {
                // if not available, load full product info to get product vendor
                $product = $this->_hlp->createObj('\Magento\Catalog\Model\Product')->load($pId);
            }
            $vId = $product->getUdropshipVendor() ? $product->getUdropshipVendor() : $localVendorId;
            $v = $hlp->getVendor($vId);
            $sku = $product->getVendorSku() ? $product->getVendorSku() : $product->getSku();
            $requestVendors = array(
            	$vId=>array(
            		'sku'=>$sku,
                    'address_match' => $v->isAddressMatch($hlp->getAddressByItem($item)),
            		'zipcode_match' => $v->isZipcodeMatch($hlp->getZipcodeByItem($item)),
                    'country_match' => $v->isCountryMatch($hlp->getCountryByItem($item)),
            	)
            );
            if (!empty($options['request_local'])) {
                $requestVendors[$localVendorId] = array(
                	'sku'=>$product->getSku(),
                    'address_match' => $hlp->getVendor($localVendorId)->isAddressMatch($hlp->getAddressByItem($item)),
                	'zipcode_match' => $hlp->getVendor($localVendorId)->isZipcodeMatch($hlp->getZipcodeByItem($item)),
                    'country_match' => $hlp->getVendor($localVendorId)->isCountryMatch($hlp->getCountryByItem($item)),
                );
            }

            $method = $v->getStockcheckMethod() ? $v->getStockcheckMethod() : 'local';
            $cb = $v->getStockcheckCallback($method);
            if (!$cb) {
                continue;
            }
            if (empty($requests[$method])) {
                $requests[$method] = array(
                    'callback' => $cb,
                    'products' => array(),
                );
            }
            if (empty($requests[$method]['products'][$pId])) {
                $requests[$method]['products'][$pId] = array(
                    'stock_item' => $this->_stockRegistry->getStockItem($pId),
                    'qty_requested' => 0,
                    'vendors' => $requestVendors,
                );
            }

            $requests[$method]['products'][$pId]['qty_requested'] += $hlp->getItemStockCheckQty($item);

        }

        $iHlp->processSameVendorLimitation($items, $requests);

        $result = $this->processRequests($items, $requests);
        $this->setStockResult($result);
        return $this;
    }

    public function processRequests($items, $requests)
    {
        $stock = array();
        foreach ($items as $item) {
            if (!$item->getHasChildren()/* && !$iHlp->isVirtual($item)*/) {
                $stock[$item->getProductId()] = array();
            }
        }
        foreach ($requests as $request) {
            try {
                $result = call_user_func($request['callback'], $request['products']);
            } catch (\Exception $e) {
                continue;
            }
            if (!empty($result)) {
                foreach ($result as $pId=>$vendors) {
                    foreach ($vendors as $vId=>$v) {
                        $stock[$pId][$vId] = $v;
                    }
                }
            }
        }

        foreach ($items as $item) {
            $pId = $item->getProductId();
            $item->setUdropshipStockLevels(!empty($stock[$pId]) ? $stock[$pId] : array());
            if ($item->getHasChildren()) {
                $children = $item->getChildrenItems() ? $item->getChildrenItems() : $item->getChildren();
                foreach ($children as $child) {
                    $pId = $child->getProductId();
                    $child->setUdropshipStockLevels(!empty($stock[$pId]) ? $stock[$pId] : array());
                    if (!$item->isShipSeparately()) {
                        $item->setUdropshipStockLevels(!empty($stock[$pId]) ? $stock[$pId] : array());
                    }
                }
            }
        }
        
        return $stock;
    }

    public function checkLocalStockLevel($products)
    {
        $this->setTrueStock(true);
        $result = array();
        $ignoreStockStatusCheck = $this->_registry->registry('reassignSkipStockCheck');
        $ignoreAddrCheck = $this->_registry->registry('reassignSkipAddrCheck');
        foreach ($products as $pId=>$p) {
            $stockItem = !empty($p['stock_item']) ? $p['stock_item']
                : $this->_stockRegistry->getStockItem($pId);
            $status = !$stockItem->getManageStock()
                || $stockItem->getIsInStock() && $this->_stockState->checkQty($pId, $p['qty_requested']);
            if ($ignoreStockStatusCheck) $status = true;
            foreach ($p['vendors'] as $vId=>$dummy) {
                $zipCodeMatch = (!isset($dummy['zipcode_match']) || $dummy['zipcode_match']!==false);
                $countryMatch = (!isset($dummy['country_match']) || $dummy['country_match']!==false);
                $result[$pId][$vId]['addr_status'] = $zipCodeMatch && $countryMatch;
                if ($ignoreAddrCheck) $result[$pId][$vId]['addr_status'] = true;
                $result[$pId][$vId]['status'] = $status && $result[$pId][$vId]['addr_status'];
                $result[$pId][$vId]['zipcode_match'] = $zipCodeMatch;
                $result[$pId][$vId]['country_match'] = $countryMatch;
            }
        }
        $this->setTrueStock(false);
        return $result;
    }

    public function addStockErrorMessages($items, $stock)
    {
        $hlp = $this->_hlp;
        $quote = null;
        $hasOutOfStock = false;
        $allAddressMatch = true;
        $allZipcodeMatch = true;
        $allCountryMatch = true;
        foreach ($items as $item) {
            if ($item->getOrder()) {
                return $this;
            }
            $quote = $item->getQuote();
            break;
        }
        foreach ($items as $item) {
            if ($item->getHasChildren()) {
                continue;
            }
            $vendors = @$stock[$item->getProductId()];
            if (!is_array($vendors)) {
                $vendors = array();
            }
            $outOfStock = true;
            $addressMatch = true;
            $zipCodeMatch = true;
            $countryMatch = true;
            foreach ($vendors as $vId=>$v) {
                $vObj = $hlp->getVendor($vId);
                $addressMatch = $addressMatch && $vObj->isAddressMatch($hlp->getAddressByItem($item));
                $zipCodeMatch = $zipCodeMatch && $vObj->isZipcodeMatch($hlp->getZipcodeByItem($item));
                $countryMatch = $countryMatch && $vObj->isCountryMatch($hlp->getCountryByItem($item));
                if ($this->getUseLocalStockIfAvailable($quote->getStoreId(), $vId)) {
                    $outOfStock = false;
                    break;
                }
                if (!empty($v['status'])) {
                    $outOfStock = false;
                    break;
                }
            }
            $allAddressMatch = $allAddressMatch && $addressMatch;
            $allZipcodeMatch = $allZipcodeMatch && $zipCodeMatch;
            $allCountryMatch = $allCountryMatch && $countryMatch;
            if ($outOfStock && !$item->getHasError() && !$item->getMessage()) {
                $hasOutOfStock = true;
                $item->setUdmultiOutOfStock(true);
                $message = $item->getMessage() ? $item->getMessage().'<br/>' : '';
                if (!$addressMatch) {
                    $message .= __('This item is not available for your location.');
                } elseif (!$countryMatch) {
                    $message .= __('This item is not available for your country.');
                } elseif (!$zipCodeMatch ) {
                    $message .= __('This item is not available for your zipcode.');
                } else {
                    $message .= __('This product is currently out of stock.');
                }
                $item->setHasError(true)->setMessage($message);
                if ($item->getParentItem()) {
                    $item->getParentItem()->setHasError(true)->setMessage($message);
                    $qtyOptions = $item->getParentItem()->getQtyOptions();
                    if (is_array($qtyOptions)) {
                        foreach ($qtyOptions as $qtyOption) {
                            $qtyOption->setMessage($message);
                            break;
                        }
                    }
                }
            }
        }
        if ($hasOutOfStock && !$quote->getHasError() && !$quote->getMessages()) {
            if (!$allAddressMatch) {
                $message = __('Some items are not available for your location.');
            } elseif (!$allCountryMatch) {
                $message = __('Some items are not available for your country.');
            } elseif (!$allZipcodeMatch) {
                $message = __('Some items are not available for your zipcode.');
            } else {
                $message = __('Some of the products are currently out of stock');
            }
            $quote->setHasError(true)->addMessage($message);
        }
        return $this;
    }
}