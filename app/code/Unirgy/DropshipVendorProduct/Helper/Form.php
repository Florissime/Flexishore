<?php

namespace Unirgy\DropshipVendorProduct\Helper;

use Magento\CatalogInventory\Helper\Data as HelperData;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Layout;
use Unirgy\DropshipVendorProduct\Model\Source;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;

class Form extends AbstractHelper
{
    /**
     * @var DesignInterface
     */
    protected $_viewDesignInterface;

    /**
     * @var HelperData
     */
    protected $_invConfig;

    /**
     * @var Source
     */
    protected $_prodSource;

    /**
     * @var Layout
     */
    protected $_viewLayout;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(Context $context,
        DesignInterface $viewDesignInterface, 
        \Magento\CatalogInventory\Model\Configuration $invConfig,
        Source $udprodSource,
        Layout $viewLayout,
        DropshipHelperData $dropshipHelperData
    )
    {
        $this->_viewDesignInterface = $viewDesignInterface;
        $this->_invConfig = $invConfig;
        $this->_prodSource = $udprodSource;
        $this->_viewLayout = $viewLayout;
        $this->_hlp = $dropshipHelperData;

        parent::__construct($context);
    }

    public function isIE6()
    {
        return preg_match('/MSIE [1-6]\./i', $this->_request->getServer('HTTP_USER_AGENT'));
    }

    public function isIE7()
    {
        return preg_match('/MSIE [1-7]\./i', $this->_request->getServer('HTTP_USER_AGENT'));
    }
    const MAX_QTY_VALUE = 99999999.9999;
    public function isQty($product)
    {
        return $this->_invConfig->isQty($product->getTypeId());
    }
    public function getStockItemField($field, $values)
    {
        $fieldDef = [];
        switch ($field) {
            case 'is_in_stock':
                $fieldDef = [
                    'id'       => 'stock_data_is_in_stock',
                    'type'     => 'select',
                    'name'     => 'stock_data[is_in_stock]',
                    'label'    => __('Stock Status'),
                    'options'  => $this->_prodSource->setPath('stock_status')->toOptionHash(),
                    'value'    => @$values['stock_data']['is_in_stock']
                ];
                break;
            case 'qty':
                $fieldDef = [
                    'id'       => 'stock_data_qty',
                    'type'     => 'stock_data_qty',
                    'name'     => 'stock_data[qty]',
                    'label'    => __('Stock Qty'),
                    'value'    => @$values['stock_data']['qty']*1
                ];
                break;
            case 'manage_stock':
                $fieldDef = [
                    'id'       => 'stock_data_manage_stock',
                    'use_config_id' => 'stock_data_use_config_manage_stock',
                    'default_id' => 'stock_data_manage_stock_defaultstock',
                    'type'     => 'select',
                    'name'     => 'stock_data[manage_stock]',
                    'use_config_name'     => 'stock_data[use_config_manage_stock]',
                    'label'    => __('Manage Stock'),
                    'value'    => @$values['stock_data']['manage_stock']*1,
                    'use_config_value' => @$values['stock_data']['use_config_manage_stock']*1,
                    'values'   => $this->_hlp->src()->setPath('yesno')->toOptionArray(),
                    'renderer' => $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\UseConfigElement'),
                    'vendor_use_custom_field' => 'is_udprod_manage_stock',
                    'vendor_field' => 'udprod_manage_stock',
                    'config_path' => \Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK,
                ];
                break;
            case 'backorders':
                $fieldDef = [
                    'id'       => 'stock_data_backorders',
                    'use_config_id' => 'stock_data_use_config_backorders',
                    'default_id' => 'stock_data_backorders_defaultbackorders',
                    'type'     => 'select',
                    'name'     => 'stock_data[backorders]',
                    'use_config_name'     => 'stock_data[use_config_backorders]',
                    'label'    => __('Backorders'),
                    'value'    => @$values['stock_data']['backorders']*1,
                    'use_config_value' => @$values['stock_data']['use_config_backorders']*1,
                    'values'   => $this->_prodSource->setPath('udprod_backorders')->toOptionArray(),
                    'renderer' => $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\UseConfigElement'),
                    'vendor_use_custom_field' => 'is_udprod_backorders',
                    'vendor_field' => 'udprod_backorders',
                    'config_path' => \Magento\CatalogInventory\Model\Configuration::XML_PATH_BACKORDERS,
                ];
                break;
            case 'min_qty':
                $fieldDef = [
                    'id'       => 'stock_data_min_qty',
                    'use_config_id' => 'stock_data_use_config_min_qty',
                    'default_id' => 'stock_data_min_qty_defaultqty',
                    'type'     => 'text',
                    'name'     => 'stock_data[min_qty]',
                    'use_config_name'     => 'stock_data[use_config_min_qty]',
                    'label'    => __('Qty for Item\'s Status to Become Out of Stock'),
                    'value'    => @$values['stock_data']['min_qty']*1,
                    'use_config_value' => @$values['stock_data']['use_config_min_qty']*1,
                    'renderer' => $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\UseConfigElement'),
                    'vendor_use_custom_field' => 'is_udprod_min_qty',
                    'vendor_field' => 'udprod_min_qty',
                    'config_path' => \Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_QTY,
                ];
                break;
            case 'min_sale_qty':
                $fieldDef = [
                    'id'       => 'stock_data_min_sale_qty',
                    'use_config_id' => 'stock_data_use_config_min_sale_qty',
                    'default_id' => 'stock_data_min_sale_qty_defaultqty',
                    'type'     => 'text',
                    'name'     => 'stock_data[min_sale_qty]',
                    'use_config_name'     => 'stock_data[use_config_min_sale_qty]',
                    'label'    => __('Minimum Qty Allowed in Shopping Cart'),
                    'value'    => @$values['stock_data']['min_sale_qty']*1,
                    'use_config_value' => @$values['stock_data']['use_config_min_sale_qty']*1,
                    'renderer' => $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\UseConfigElement'),
                    'vendor_use_custom_field' => 'is_udprod_min_sale_qty',
                    'vendor_field' => 'udprod_min_sale_qty',
                    'config_path' => \Magento\CatalogInventory\Model\Configuration::XML_PATH_MIN_SALE_QTY,
                ];
                break;
            case 'max_sale_qty':
                $fieldDef = [
                    'id'       => 'stock_data_max_sale_qty',
                    'use_config_id' => 'stock_data_use_config_max_sale_qty',
                    'default_id' => 'stock_data_max_sale_qty_defaultqty',
                    'type'     => 'text',
                    'name'     => 'stock_data[max_sale_qty]',
                    'use_config_name'     => 'stock_data[use_config_max_sale_qty]',
                    'label'    => __('Maximum Qty Allowed in Shopping Cart'),
                    'value'    => @$values['stock_data']['max_sale_qty']*1,
                    'use_config_value' => @$values['stock_data']['use_config_max_sale_qty']*1,
                    'renderer' => $this->_viewLayout->createBlock('Unirgy\DropshipVendorProduct\Block\Vendor\Product\Renderer\UseConfigElement'),
                    'vendor_use_custom_field' => 'is_udprod_max_sale_qty',
                    'vendor_field' => 'udprod_max_sale_qty',
                    'config_path' => \Magento\CatalogInventory\Model\Configuration::XML_PATH_MAX_SALE_QTY,
                ];
                break;
        }
        return $fieldDef;
    }
    public function getSystemField($field, $values)
    {
        $fieldDef = [];
        switch ($field) {
            case 'product_categories':
                $fieldDef = [
                    'id'       => 'product_categories',
                    'type'     => 'product_categories',
                    'name'     => 'category_ids',
                    'label'    => __('Categories'),
                    'value'    => @$values['product_categories'],
                ];
                break;
            case 'product_websites':
                $fieldDef = [
                    'id'       => 'product_websites',
                    'type'     => 'multiselect',
                    'name'     => 'website_ids',
                    'label'    => __('Websites'),
                    'value'    => @$values['product_websites'],
                    'values'   => $this->_prodSource->setPath('product_websites')->toOptionArray()
                ];
                break;
        }
        return $fieldDef;
    }
    public function getAttributeField($attribute)
    {
        $fieldDef = [];
        if ($attribute && (!$attribute->hasIsVisible() || $attribute->getIsVisible())
            && ($inputType = $attribute->getFrontend()->getInputType())
        ) {
            $fieldType      = $inputType;
            if ($attribute->getAttributeCode()=='tier_price') $fieldType='tier_price';
            if ($attribute->getAttributeCode()=='group_price') $fieldType='group_price';
            $rendererClass  = $attribute->getFrontend()->getInputRendererClass();
            if (!empty($rendererClass)) {
                $fieldType  = $inputType . '_' . $attribute->getAttributeCode();
            }
            $fieldDef = [
                'id'       => $attribute->getAttributeCode(),
                'type'     => $fieldType,
                'name'     => $attribute->getAttributeCode(),
                'label'    => $attribute->getFrontend()->getLabel(),
                'class'    => $attribute->getFrontend()->getClass(),
                'note'     => $attribute->getNote(),
                'input_renderer' => $rendererClass,
                'entity_attribute' => $attribute
            ];
            if ($inputType == 'select') {
                $fieldDef['values'] = $attribute->getSource()->getAllOptions(true, false);
            } else if ($inputType == 'multiselect') {
                $fieldDef['values'] = $attribute->getSource()->getAllOptions(false, false);
                $fieldDef['can_be_empty'] = true;
            } else if ($inputType == 'date') {
                $fieldDef['date_format'] = $this->_hlp->getDefaultDateFormat();
                if ($attribute->getAttributeCode() == 'special_from_date') {
                    $fieldDef['class']  = 'validate-date validate-date-range date-range-special_date-from';
                } elseif ($attribute->getAttributeCode() == 'special_to_date') {
                    $fieldDef['class']  = 'validate-date validate-date-range date-range-special_date-to';
                }
            } else if ($inputType == 'multiline') {
                $fieldDef['line_count'] = $attribute->getMultilineCount();
            }
        }
        return $fieldDef;
    }
    public function getUdmultiField($field, $mvData)
    {
        $fieldDef = [];
        switch ($field) {
            case 'status':
                $fieldDef = [
                    'id' => 'udmulti_status',
                    'type'     => 'select',
                    'name'     => 'udmulti[status]',
                    'label'    => __('Status'),
                    'options'   => $this->_hlp->getObj('\Unirgy\DropshipMulti\Model\Source')->setPath('vendor_product_status')->toOptionHash(),
                    'value'     => @$mvData['status']
                ];
                break;
            case 'state':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $fieldDef = [
                    'id' => 'udmulti_state',
                    'type'     => 'select',
                    'name'     => 'udmulti[state]',
                    'label'    => __('State (Condition)'),
                    'options'  => $this->_hlp->getObj('Unirgy\DropshipMultiPrice\Model\Source')->setPath('vendor_product_state')->toOptionHash(),
                    'value'    => @$mvData['state']
                ];
                }
                break;
            case 'stock_qty':
                $v = @$mvData['stock_qty'];
                $fieldDef = [
                    'id' => 'udmulti_stock_qty',
                    'type'     => 'text',
                    'name'     => 'udmulti[stock_qty]',
                    'label'    => __('Stock Qty'),
                    'value'    => null !== $v ? $v*1 : ''
                ];
                break;
            case 'state_descr':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $fieldDef = [
                    'id' => 'udmulti_state_descr',
                    'type'     => 'text',
                    'name'     => 'udmulti[state_descr]',
                    'label'    => __('State description'),
                    'value'    => @$mvData['state_descr']
                ];
                }
                break;
            case 'vendor_title':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $fieldDef = [
                    'id' => 'udmulti_vendor_title',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_title]',
                    'label'    => __('Vendor Title'),
                    'value'    => @$mvData['vendor_title']
                ];
                }
                break;
            case 'vendor_cost':
                $v = @$mvData['vendor_cost'];
                $fieldDef = [
                    'id' => 'udmulti_vendor_cost',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_cost]',
                    'label'    => __('Vendor Cost'),
                    'value'    => null !== $v ? $v*1 : ''
                ];
                break;
            case 'vendor_price':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $v = @$mvData['vendor_price'];
                $fieldDef = [
                    'id' => 'udmulti_vendor_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_price]',
                    'label'    => __('Vendor Price'),
                    'value'    => null !== $v ? $v*1 : ''
                ];
                }
                break;
            case 'group_price':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                    $v = @$mvData['group_price'];
                    $fieldDef = [
                        'id' => 'udmulti_group_price',
                        'type'     => 'udmulti_group_price',
                        'name'     => 'udmulti[group_price]',
                        'input_renderer' => '\Unirgy\DropshipMulti\Block\Vendor\ProductAttribute\Form\GroupPrice',
                        'label'    => __('Group Price'),
                        'value'    => !empty($v) && is_array($v) ? $v : []
                    ];
                }
                break;
            case 'tier_price':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                    $v = @$mvData['tier_price'];
                    $fieldDef = [
                        'id' => 'udmulti_tier_price',
                        'type'     => 'udmulti_tier_price',
                        'name'     => 'udmulti[tier_price]',
                        'input_renderer' => '\Unirgy\DropshipMulti\Block\Vendor\ProductAttribute\Form\TierPrice',
                        'label'    => __('Tier Price'),
                        'value'    => !empty($v) && is_array($v) ? $v : []
                    ];
                }
                break;
            case 'special_price':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $v = @$mvData['special_price'];
                $fieldDef = [
                    'id' => 'udmulti_special_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[special_price]',
                    'label'    => __('Vendor Special Price'),
                    'value'    => null !== $v ? $v*1 : ''
                ];
                }
                break;
            case 'special_from_date':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $fieldDef = [
                    'id' => 'udmulti_special_from_date',
                    'type'     => 'date',
                    'date_format'   => $this->_hlp->getDefaultDateFormat(),
                    'name'     => 'udmulti[special_from_date]',
                    'label'    => __('Vendor Special From Date'),
                    'value'    => @$mvData['special_from_date'],
                    'class'    => 'validate-date validate-date-range date-range-udmulti_special_date-from'
                ];
                }
                break;
            case 'special_to_date':
                if ($this->_hlp->isUdmultiPriceAvailable()) {
                $fieldDef = [
                    'id' => 'udmulti_special_to_date',
                    'type'     => 'date',
                    'date_format'   => $this->_hlp->getDefaultDateFormat(),
                    'name'     => 'udmulti[special_to_date]',
                    'label'    => __('Vendor Special To Date'),
                    'value'    => @$mvData['special_to_date'],
                    'class'    => 'validate-date validate-date-range date-range-udmulti_special_date-to'
                ];
                }
                break;
            case 'vendor_sku':
                $fieldDef = [
                    'id' => 'udmulti_vendor_sku',
                    'type'     => 'text',
                    'name'     => 'udmulti[vendor_sku]',
                    'label'    => __('Vendor Sku'),
                    'value'    => @$mvData['vendor_sku']
                ];
                break;
            case 'freeshipping':
                $fieldDef = [
                    'id' => 'udmulti_freeshipping',
                    'type'     => 'select',
                    'name'     => 'udmulti[freeshipping]',
                    'label'    => __('Is Free Shipping'),
                    'options'  => $this->_hlp->src()->setPath('yesno')->toOptionHash(),
                    'value'    => @$mvData['freeshipping']*1
                ];
                break;
            case 'shipping_price':
                $fieldDef = [
                    'id' => 'udmulti_shipping_price',
                    'type'     => 'text',
                    'name'     => 'udmulti[shipping_price]',
                    'label'    => __('Shipping Price'),
                    'value'    => @$mvData['shipping_price']
                ];
                break;
            case 'backorders':
                $fieldDef = [
                    'id' => 'udmulti_backorders',
                    'type'     => 'select',
                    'name'     => 'udmulti[backorders]',
                    'label'    => __('Vendor Backorders'),
                    'options'  => $this->_hlp->getObj('\Unirgy\DropshipMulti\Model\Source')->setPath('backorders')->toOptionHash(),
                    'value'    => @$mvData['backorders']*1
                ];
                break;
        }
        return $fieldDef;
    }
}