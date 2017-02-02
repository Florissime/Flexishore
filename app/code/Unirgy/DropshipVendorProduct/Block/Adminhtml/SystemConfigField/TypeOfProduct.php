<?php

namespace Unirgy\DropshipVendorProduct\Block\Adminhtml\SystemConfigField;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ProductFactory;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;

class TypeOfProduct extends Field
{
    /**
     * @var Collection
     */
    protected $_setCollection;

    /**
     * @var ProductFactory
     */
    protected $_modelProductFactory;


    protected $_element = null;

    public function __construct(
        Context $context,
        Collection $setCollection,
        ProductFactory $modelProductFactory, 
        array $data = [])
    {
        $this->_setCollection = $setCollection;
        $this->_modelProductFactory = $modelProductFactory;

        parent::__construct($context, $data);
        if (!$this->getTemplate()) {
            $this->setTemplate('Unirgy_DropshipVendorProduct::udprod/system/form_field/type_of_product.phtml');
        }
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        return $html;
    }

    public function getStore()
    {
        return $this->_storeManager->getDefaultStoreView();
    }

    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData([
                    'label' => __('Delete'),
                    'class' => 'delete delete-option'
                ]));
        $this->setChild('add_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData([
                    'label' => __('Add'),
                    'class' => 'add',
                    'id'    => 'udprodTypeOfProduct_config_add_new_option_button'
                ]));
        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    public function getAttributeSetSelect($name, $value=null, $id=null)
    {
        $select = $this->getLayout()->createBlock('Magento\Framework\View\Element\Html\Select')
            ->setClass('required-entry validate-state')
            ->setValue($value)
            ->setExtraParams('multiple="multiple" style="height: 300px"')
            ->setOptions($this->getSetIds());

        $select->setName($name);
        if (!is_null($id)) $select->setId($id);

        return $select->getHtml();
    }

    public function getSetIds()
    {
        return $this->_setCollection
            ->setEntityTypeFilter($this->_modelProductFactory->create()->getResource()->getEntityType()->getId())
            ->load()
            ->toOptionHash();
    }

}