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
 * @package    Unirgy_DropshipTierShipping
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipTierShipping\Model\ProductAttributeBackend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Unirgy\DropshipTierShipping\Helper\Data as HelperData;

/**
 * Class Rates
 * @package Unirgy\DropshipTierShipping\Model\ProductAttributeBackend
 */
class Rates extends AbstractBackend
{
    /**
     * @var HelperData
     */
    protected $_helper;

    /**
     * Rates constructor.
     * @param HelperData $helperData
     */
    public function __construct(HelperData $helperData)
    {
        $this->_helper = $helperData;

    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return $this|void
     */
    public function afterLoad($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
        try {
            $decoded = $this->_helper->getProductV2Rates($object, null);
            $decoded = array_values($decoded);
            $object->setData($attrCode, $decoded);
        } catch (\Exception $e) {
        }

    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function sortBySortOrder($a, $b)
    {
        if (@$a['sort_order'] < @$b['sort_order']) {
            return -1;
        } elseif (@$a['sort_order'] > @$b['sort_order']) {
            return 1;
        }
        return 0;
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return void
     */
    public function beforeSave($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
        if (($attrValue = $object->getData($attrCode)) && is_array($attrValue)) {
            unset($attrValue['$ROW']);
            unset($attrValue['$$ROW']);
            usort($attrValue, [$this, 'sortBySortOrder']);
            //$this->_helper->saveProductV2Rates($object, $attrValue);
            $object->setData('__' . $attrCode, $attrValue);
            $object->setData($attrCode, '');
        }
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    public function afterSave($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
        try {
            if ($object->hasData('__' . $attrCode)) {
                $this->_helper->saveProductV2Rates($object, $object->getData('__' . $attrCode));
            }
            $object->unsetData('__' . $attrCode);
            $decoded = $this->_helper->getProductV2Rates($object, null);
            $object->setData($attrCode, $decoded);
        } catch (\Exception $e) {
        }
        return $this;
    }
}
