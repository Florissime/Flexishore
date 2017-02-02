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
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
namespace Unirgy\DropshipVendorProduct\Model;

use Magento\CatalogInventory\Model\Source\Backorders;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\WebsiteFactory;
use Unirgy\DropshipVendorProduct\Helper\Data as DropshipVendorProductHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipVendorProductHelperData
     */
    protected $_prodHlp;

    /**
     * @var WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var Backorders
     */
    protected $_sourceBackorders;

    public function __construct(
        ScopeConfigInterface $configScopeConfigInterface, 
        HelperData $helperData, 
        DropshipVendorProductHelperData $dropshipVendorProductHelperData, 
        WebsiteFactory $modelWebsiteFactory, 
        Backorders $sourceBackorders,
        array $data = []
    )
    {
        $this->_scopeConfig = $configScopeConfigInterface;
        $this->_hlp = $helperData;
        $this->_prodHlp = $dropshipVendorProductHelperData;
        $this->_websiteFactory = $modelWebsiteFactory;
        $this->_sourceBackorders = $sourceBackorders;

        parent::__construct($data);
    }

    const MEDIA_CFG_SHOW_EXPLICIT=1;
    const MEDIA_CFG_PER_OPTION_HIDDEN=2;
    public function isCfgUploadImagesSimple($store=null)
    {
        return $this->_scopeConfig->isSetFlag('udprod/general/cfg_upload_images_simple', ScopeInterface::SCOPE_STORE, $store);
    }
    public function isMediaCfgPerOptionHidden($store=null)
    {
        return self::MEDIA_CFG_PER_OPTION_HIDDEN==$this->_scopeConfig->getValue('udprod/general/cfg_show_media_gallery', ScopeInterface::SCOPE_STORE, $store);
    }
    public function isMediaCfgShowExplicit($store=null)
    {
        return self::MEDIA_CFG_SHOW_EXPLICIT==$this->_scopeConfig->getValue('udprod/general/cfg_show_media_gallery', ScopeInterface::SCOPE_STORE, $store);
    }
    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $prHlp = $this->_prodHlp;

        switch ($this->getPath()) {

        case 'is_limit_categories':
            $options = [
                0 => __('No'),
                1 => __('Enable Selected'),
                2 => __('Disable Selected'),
            ];
            break;

        case 'udprod/general/cfg_show_media_gallery':
            $options = [
                0 => __('No'),
                1 => __('Yes'),
                2 => __('Yes and hide per option upload'),
            ];
            break;
        case 'udprod/quick_create_layout/cfg_attributes':
            $options = [
                'one_column'      => __('One Column'),
                'separate_column' => __('Separate Columns'),
            ];
            break;
        case 'udprod_unpublish_actions':
        case 'udprod/general/unpublish_actions':
            $options = [
                'none'               => __('None'),
                'all'                => __('All'),
                'new_product'        => __('New Product'),
                'image_added'        => __('Image Added'),
                'image_removed'      => __('Image Removed'),
                'cfg_simple_added'   => __('Configurable Simple Added'),
                'cfg_simple_removed' => __('Configurable Simple Removed'),
                'attribute_changed'  => __('Attribute Value Changed'),
                'stock_changed'      => __('Stock Changed'),
                'custom_options_changed' => __('Custom Options Changed'),
            ];
            break;
        case 'udprod_allowed_types':
        case 'udprod/general/allowed_types':
            $at = $this->_scopeConfig->getValue('udprod/general/type_of_product', ScopeInterface::SCOPE_STORE);
            if (is_string($at)) {
                $at = unserialize($at);
            }
            $options = [
                '*none*' => __('* None *'),
                '*all*'  => __('* All *'),
            ];
            if (is_array($at)) {
                foreach ($at as $_at) {
                    $options[$_at['type_of_product']] = $_at['type_of_product'];
                }
            }
            break;
        case 'stock_status':
            $options = [
                0 => __('Out of stock'),
                1 => __('In stock'),
            ];
            break;
        case 'system_status':
            $options = [
                1 => __('Published'),
                2 => __('Disabled'),
                3 => __('Under Review'),
                4 => __('Fix'),
                5 => __('Discard'),
            ];
            break;

        case 'udprod/template_sku/type_of_product':
            $selector = true;
            $_options = $this->_scopeConfig->getValue('udprod/general/type_of_product', ScopeInterface::SCOPE_STORE);
            if (!is_array($_options)) {
                $_options = unserialize($_options);
            }
            $options = [];
            if (!empty($_options) && is_array($_options)) {
                foreach ($_options as $opt) {
                    $_val = $opt['type_of_product'];
                    $options[$_val] = $_val;
                }
            }
            break;

        case 'product_websites':
            $collection = $this->_websiteFactory->create()->getResourceCollection();
            $options = ['' => __('* None')];
            foreach ($collection as $w) {
                $options[$w->getId()] = $w->getName();
            }
            break;

        case 'udprod_backorders':
            $options = [];
            foreach ($this->_sourceBackorders->toOptionArray() as $opt) {
                $options[$opt['value']] = $opt['label'];
            }
            break;

        default:
            throw new \Exception(__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = [''=>__('* Please select')] + $options;
        }

        return $options;
    }
}