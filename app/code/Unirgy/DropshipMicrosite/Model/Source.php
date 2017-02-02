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

namespace Unirgy\DropshipMicrosite\Model;

use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\WebsiteFactory;
use Unirgy\DropshipMicrosite\Helper\Data as DropshipMicrositeHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source as ModelSource;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipMicrositeHelperData
     */
    protected $_msHlp;

    /**
     * @var ModelSource
     */
    protected $_src;

    /**
     * @var WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var \Magento\Cms\Model\Config\Source\Page
     */
    protected $_cmsPageSource;

    /**
     * @var Config
     */
    protected $_eavConfig;

    public function __construct(
        HelperData $helperData, 
        DropshipMicrositeHelperData $dropshipMicrositeHelperData, 
        ModelSource $modelSource, 
        WebsiteFactory $modelWebsiteFactory, 
        \Magento\Cms\Model\Config\Source\Page $cmsPageSource,
        Config $eavConfig,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_msHlp = $dropshipMicrositeHelperData;
        $this->_src = $modelSource;
        $this->_websiteFactory = $modelWebsiteFactory;
        $this->_cmsPageSource = $cmsPageSource;
        $this->_eavConfig = $eavConfig;

        parent::__construct($data);
    }

    const AUTO_APPROVE_NO = 0;
    const AUTO_APPROVE_YES = 1;
    const AUTO_APPROVE_YES_ACTIVE = 2;

    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $hlpc = $this->_msHlp;

        switch ($this->getPath()) {

        case 'subdomain_level';
        case 'udropship/microsite/subdomain_level':
            $options = [
                0 => __('Disable'),
                1 => __('From URL Path (domain.com/vendor)'),
                2 => __('2nd level subdomain (vendor.com)'),
                3 => __('3rd level subdomain (vendor.domain.com)'),
                4 => __('4th level subdomain (vendor.subdomain.domain.com)'),
                5 => __('5th level subdomain (vendor.subdomain2.subdomain1.domain.com)'),
            ];
            if ($this->getPath()=='subdomain_level') {
                $options[0] = __('* Use Config');
            }
            break;

        case 'udropship/microsite/auto_approve':
            $options = [
                self::AUTO_APPROVE_NO => __('No'),
                self::AUTO_APPROVE_YES => __('Yes'),
                self::AUTO_APPROVE_YES_ACTIVE => __('Yes and activate'),
            ];
            break;

        case 'udropship/stock/stick_microsite':
            $options = [
                0 => __('No'),
                1 => __('Yes'),
                2 => __('Yes and display vendor'),
                3 => __('Yes (only when in stock)'),
                4 => __('Yes (only when in stock) and display vendor'),
            ];
            break;

        case 'is_limit_categories':
            $options = [
                0 => __('No'),
                1 => __('Enable Selected'),
                2 => __('Disable Selected'),
            ];
            break;

        case 'udropship/microsite/registration_carriers':
            $options = $this->_src->getCarriers();
            $selector = false;
            break;

        case 'udropship/microsite/template_vendor':
            $options = $this->_src->getVendors(true);
            $selector = false;
            break;

        case 'udropship/microsite/registration_services': // not used
            $options = [];
            $collection = $hlp->getShippingMethods();
            foreach ($collection as $shipping) {
                $options[$shipping->getId()] = $shipping->getShippingTitle().' ['.$shipping->getShippingCode().']';
            }
            $selector = false;
            break;

        case 'limit_websites':
        case 'udropship/microsite/staging_website':
            $collection = $this->_websiteFactory->create()->getResourceCollection();
            $options = ['' => __('* None')];
            foreach ($collection as $w) {
                $options[$w->getId()] = $w->getName();
            }
            break;

        case 'carrier_code':
        case 'registration_carriers':
            $options = [];
            $carriers = explode(',', $this->_hlp->getScopeConfig('udropship/microsite/registration_carriers'));
            foreach ($carriers as $code) {
                $options[$code] = $this->_hlp->getScopeConfig("carriers/{$code}/title");
            }
            break;
            
        case 'udropship/microsite/hide_product_attributes':
            $options = $this->getVisibleProductAttributes();
            break;

        case 'cms_landing_page':
            $_options = $this->_cmsPageSource->toOptionArray();
            $options[-1] = __('* Use config');
            foreach ($_options as $_opt) {
                $options[$_opt['value']] = $_opt['label'];
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
    
    protected $_visibleProductAttributes;
    public function getVisibleProductAttributes()
    {
        if (!$this->_visibleProductAttributes) {
            $entityType = $this->_eavConfig->getEntityType('catalog_product');
            $attrs = $entityType->getAttributeCollection()
                ->addFieldToFilter('is_visible', 1)
                ->addFieldToFilter('attribute_code', ['nin'=>['', 'udropship_vendor']])
                ->setOrder('frontend_label', 'asc');
            $this->_visibleProductAttributes = [];
            foreach ($attrs as $a) {
                $this->_visibleProductAttributes[$a->getAttributeCode()] = $a->getFrontendLabel().' ['.$a->getAttributeCode().']';
            }
        }
        return $this->_visibleProductAttributes;
    }
}
