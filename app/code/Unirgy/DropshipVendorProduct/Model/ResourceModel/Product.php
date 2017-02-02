<?php

namespace Unirgy\DropshipVendorProduct\Model\ResourceModel;

use Magento\Catalog\Model\Factory;
use Magento\Catalog\Model\Product\Attribute\DefaultAttributes;
use Magento\Catalog\Model\ResourceModel\AbstractResource;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ResourceModelProduct;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\Dropship\Helper\Catalog;
use Unirgy\Dropship\Helper\Data as HelperData;

class Product extends ResourceModelProduct
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    public function __construct(Context $context, 
        StoreManagerInterface $storeManager, 
        Factory $modelFactory, 
        CollectionFactory $categoryCollectionFactory, 
        Category $catalogCategory, 
        ManagerInterface $eventManager, 
        SetFactory $setFactory, 
        TypeFactory $typeFactory, 
        DefaultAttributes $defaultAttributes, 
        HelperData $helperData, 
        ScopeConfigInterface $scopeConfig,
        Catalog $helperCatalog)
    {
        $this->_hlp = $helperData;
        $this->_scopeConfig = $scopeConfig;
        $this->_helperCatalog = $helperCatalog;

        parent::__construct($context, $storeManager, $modelFactory, $categoryCollectionFactory, $catalogCategory, $eventManager, $setFactory, $typeFactory, $defaultAttributes);
    }

    protected function _beforeSave(DataObject $object)
    {
        if ($object->hasCategoryIds()) {
            $categoryIds = $this->_hlp->getObj('\Magento\Catalog\Model\ResourceModel\Category')->verifyIds(
                $object->getCategoryIds()
            );
            $object->setCategoryIds($categoryIds);
        }
        $vId = ObjectManager::getInstance()->get('Unirgy\Dropship\Model\Session')->getVendorId();
        if (!$vId && $this->_hlp->isAdmin()
            && $this->_hlp->isModuleActive('umicrosite')
            && ($v = $this->_hlp->getObj('Unirgy\DropshipMicrosite\Helper\Data')->getAdminhtmlVendor())
        ) {
            $vId = $v->getId();
        } else {
            $vId = $object->getData('udropship_vendor');
        }
        $prefixSkuVid = $this->_hlp->getScopeFlag('udprod/general/prefix_sku_vid');
        if (!$object->getSku() && $this->_hlp->getScopeFlag('udprod/general/auto_sku')) {
            $adapter = $this->getConnection();
            $pidSuffix = $adapter->fetchOne($adapter->select()
                ->from($this->getEntityTable(), 'max(entity_id)'));
            do {
                $__checkSku = ++$pidSuffix;
                if ($prefixSkuVid && $vId) {
                    $__checkSku = $vId.'-'.$__checkSku;
                }
                $object->setSku($__checkSku);
            } while ($this->_helperCatalog->getPidBySkuForUpdate($object->getSku(), $object->getId()));
        }
        if ($prefixSkuVid && $vId && 0 !== strpos($object->getSku(), $vId.'-')) {
            $object->setSku($vId.'-'.$object->getSku());
        }
        if ($this->_scopeConfig->isSetFlag('udprod/general/unique_vendor_sku', ScopeInterface::SCOPE_STORE)
            && $vId
            && !$this->_hlp->isUdmultiActive()
        ) {
            $vSkuAttr = $this->_scopeConfig->getValue('udropship/vendor/vendor_sku_attribute', ScopeInterface::SCOPE_STORE);
            if ($vSkuAttr && $vSkuAttr!='sku') {
                if (!$object->getData($vSkuAttr)) {
                    throw new \Exception('Vendor SKU attribute is empty');
                } elseif ($this->_helperCatalog->getPidByVendorSku($object->getData($vSkuAttr), $vId, $object->getId())) {
                    throw new \Exception(__('Vendor SKU "%1" is already used', $object->getData($vSkuAttr)));
                }
            }
        }
        if ($this->_helperCatalog->getPidBySku($object->getSku(), $object->getId())) {
            throw new \Exception(__('SKU "%1" is already used', $object->getSku()));
        }

        return AbstractResource::_beforeSave($object);
    }
}