<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ProductFactory as ModelProductFactory;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Layout;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipVendorProduct\Model\ProductFactory;

class TemplateSku extends Field
{
    /**
     * @var ProductFactory
     */
    protected $_uProductFactory;

    /**
     * @var Collection
     */
    protected $_setCollection;

    /**
     * @var ModelProductFactory
     */
    protected $_productFactory;

    protected $_element = null;

    public function __construct(
        Context $context,
        ProductFactory $modelProductFactory,
        Collection $setCollection, 
        ModelProductFactory $catalogModelProductFactory, 
        array $data = [])
    {
        $this->_uProductFactory = $modelProductFactory;
        $this->_setCollection = $setCollection;
        $this->_productFactory = $catalogModelProductFactory;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipVendorProduct::udprod/system/form_field/template_sku_config.phtml');
        }
    }

    public function getElementHtml(AbstractElement $element)
    {
        return $this->_getElementHtml($element);
    }
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        if (!$this->getTypeOfProduct()) {
            $html = '<div id="'.$element->getHtmlId().'_container"></div>';
        } else {
            $html = $this->_toHtml();
        }
        return $html;
    }

    public function getConfigurableAttributes($setId)
    {
        static $prod;
        if (null === $prod) {
            $prod = $this->_uProductFactory->create()->setTypeId('configurable');
        }
        list($_setId) = explode('-', $setId);
        $prod->setAttributeSetId($_setId);
        $_cfgAttributes = [];
        $cfgAttributes = $prod->getTypeInstance(true)
            ->getSetAttributes($prod);
        foreach ($cfgAttributes as $cfgAttribute) {
            if ($prod->getTypeInstance(true)->canUseAttribute($cfgAttribute, $prod)) {
                $_cfgAttributes[$cfgAttribute->getId()] = $cfgAttribute->getFrontend()->getLabel();
            }
        }
        return $_cfgAttributes;
    }

    public function getSetIds()
    {
        $_setIds = $this->_setCollection
            ->setEntityTypeFilter($this->_productFactory->create()->getResource()->getEntityType()->getId())
            ->load()
            ->toOptionHash();
        $setIds = [];
        $_options = $this->_scopeConfig->getValue('udprod/general/type_of_product', ScopeInterface::SCOPE_STORE);
        if (!is_array($_options)) {
            $_options = unserialize($_options);
        }
        $options = [];
        if (is_array($_options)) {
            foreach ($_options as $opt) {
                if ($this->getTypeOfProduct() == $opt['type_of_product']) {
                    $options = $opt['attribute_set'];
                    break;
                }
            }
        }
        foreach ($_setIds as $_setId => $_setIdLbl) {
            if (in_array($_setId, $options)) {
                $setIds[$_setId] = $_setIdLbl;
            }
        }
        return $setIds;
    }

    public function getCfgValue($cfg, $key, $subKey)
    {
        if (false === strpos($key, '-')) {
            $key = $key.'-'.$this->getTypeOfProduct();
        }
        list($_key) = explode('-', $key);
        $result = @$cfg[$key][$subKey];
        if (!isset($cfg[$key])) {
            $result = @$cfg[$_key][$subKey];
        }
        return $result;
    }

}