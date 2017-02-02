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
namespace Unirgy\DropshipMicrositePro\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Unirgy\DropshipMicrositePro\Helper\Data as DropshipMicrositeProHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipMicrositeProHelperData
     */
    protected $_mspHlp;

    /**
     * @var \Magento\Cms\Model\Config\Source\Page
     */
    protected $_cmsPage;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        HelperData $udropshipHelper,
        DropshipMicrositeProHelperData $micrositeProHelper,
        \Magento\Cms\Model\Config\Source\Page $cmsPage,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_mspHlp = $micrositeProHelper;
        $this->_cmsPage = $cmsPage;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($data);
    }

    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $sHlp = $this->_mspHlp;

        $selectorLabel = '* Please select';

        switch ($this->getPath()) {

        case 'cms_landing_page':
            $_options = $this->_cmsPage->toOptionArray();
            $options[-1] = __('* Use config');
            foreach ($_options as $_opt) {
                $options[$_opt['value']] = $_opt['label'];
            }
            break;
        case 'billing_region_id':
        case 'region_id':
            $selectorLabel = 'Please select region, state or province';
            $options = [
            ];
            break;
        case 'billing_country_id':
        case 'country_id':
            $selectorLabel = 'Please select region, state or province';
            $options = [
            ];
            break;

        case 'agree_terms_conditions':
            $options = [
                '1' => __('Yes Agree')
            ];
            break;

        default:
            throw new \Exception(__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = [''=>__($selectorLabel)] + $options;
        }

        return $options;
    }

    protected $_vendorPreferences = [];
    public function getVendorPreferences($filterVisible=false)
    {
        if (!isset($this->_vendorPreferences[$filterVisible])) {
            $hlp = $this->_hlp;

            $visible = $this->scopeConfig->getValue('udropship/vendor/visible_preferences', ScopeInterface::SCOPE_STORE);
            $visible = $visible ? explode(',', $visible) : false;

            $fieldsets = [];
            foreach ($this->_hlp->config()->getFieldset() as $code=>$node) {
                $node = (object)$node;
                if (@$node->modules && !$hlp->isModulesActive((string)$node->modules)) {
                    continue;
                }
                $fieldsets[$code] = [
                    'position' => (int)@$node->position,
                    'label' => (string)$node->legend,
                    'value' => [],
                ];
            }
            foreach ($this->_hlp->config()->getField() as $code=>$node) {
                $node = (object)$node;
                if (!@$node->fieldset || empty($fieldsets[(string)@$node->fieldset]) || @$node->disabled) {
                    continue;
                }
                if (@$node->modules && !$hlp->isModulesActive((string)$node->modules)) {
                    continue;
                }
                if ($filterVisible && $visible && !in_array($code, $visible)) {
                    continue;
                }
                $field = [
                    'position' => (int)@$node->position,
                    'label' => (string)$node->label,
                    'value' => $code,
                ];
                $fieldsets[(string)$node->fieldset]['value'][] = $field;
            }
            uasort($fieldsets, [$hlp, 'usortByPosition']);
            foreach ($fieldsets as $k=>$v) {
                if (empty($v['value'])) {
                    continue;
                }
                uasort($v['value'], [$hlp, 'usortByPosition']);
            }
            $this->_vendorPreferences[$filterVisible] = $fieldsets;
        }
        return $this->_vendorPreferences[$filterVisible];
    }
}