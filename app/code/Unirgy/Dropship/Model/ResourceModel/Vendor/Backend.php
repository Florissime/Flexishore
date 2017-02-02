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

namespace Unirgy\Dropship\Model\ResourceModel\Vendor;

use \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Backend extends AbstractBackend
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HelperData $helper)
    {
        $this->scopeConfig = $scopeConfig;
        $this->_hlp = $helper;

    }

    protected function _isEnabled()
    {
        return $this->_hlp->isActive();
    }

    public function getDefaultValue()
    {
        return parent::getDefaultValue();
    }

    public function afterLoad($object)
    {
        parent::afterLoad($object);
        if (!$this->_isEnabled()) {
            return;
        }
        $attrCode = $this->getAttribute()->getAttributeCode();
        $defValue = $this->getDefaultValue();
        if (!$object->getData($attrCode) && $defValue) {
            $object->setData($attrCode, $defValue);
        }
    }
}