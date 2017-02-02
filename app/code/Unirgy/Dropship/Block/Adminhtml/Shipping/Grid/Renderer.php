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

namespace Unirgy\Dropship\Block\Adminhtml\Shipping\Grid;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \Magento\Framework\DataObject;

class Renderer
    extends AbstractRenderer
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    /**
     * @var \Unirgy\Dropship\Model\Source
     */
    protected $_src;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Block\Context $context,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Unirgy\Dropship\Model\Source $source,
        array $data = []
    )
    {
        $this->_storeManager = $storeManager;
        $this->_shippingConfig = $shippingConfig;
        $this->_src = $source;

        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $index = $this->getColumn()->getIndex();
        $value = $row->getData($index);
        switch ($index) {
        case 'system_methods_by_profile':
            return $this->_renderMethods($value);
        case 'website_ids':
            return $this->_renderWebsites($value);
        }
    }

    protected function _renderMethods($systemMethods)
    {
        if (!$systemMethods) {
            return '';
        }
        $carriers = $this->_shippingConfig->getAllCarriers();
        foreach ($carriers as $carrierCode=>$carrierModel) {
            /*
            if (!$carrierModel->isActive() && (bool)$isActiveOnlyFlag === true) {
                continue;
            }
            */
            if ($carrierCode=='ups') {
                $methodsNested = $this->_src->setPath('ups_shipping_method_combined')->toOptionHash();
                foreach ($methodsNested as $api=>$ms) {
                    foreach ($ms as $k=>$v) {
                        $carrierMethods[$k] = "($api) $v";
                    }
                }
            } else {
                $carrierMethods = $carrierModel->getAllowedMethods();
                if (!$carrierMethods) {
                    $carrierMethods = array();
                }
            }
            $carrierTitle = $this->_scopeConfig->getValue('carriers/'.$carrierCode.'/title');
            foreach ($carrierMethods as $methodCode=>$methodTitle) {
                $methods[$carrierCode][$methodCode] = $carrierTitle.' - '.$methodTitle;
            }
            $methods[$carrierCode]['*'] = $carrierTitle.' - Any available';
        }

        $result = array();
        foreach ($systemMethods as $p=>$__m) {
            foreach ($__m as $c=>$_m) {
                foreach ($_m as $m) {
                    if (isset($methods[$c][$m])) {
                        $result[] = $methods[$c][$m];
                    } else {
                        $result[] = $c.' - '.$m.' (not found)';
                    }
                }
            }
        }

        return $result ? join('<br/> ', $result) : '&nbsp;';

    }

    protected function _renderWebsites($websites)
    {
        $result = array();
        if ($websites == array(0)) {
            return __('All websites');
        }
        foreach ($websites as $id) {
            $result[] = $this->_storeManager->getWebsite($id)->getName();
        }
        return $result ? join('<br/> ', $result) : '&nbsp;';
    }

}
