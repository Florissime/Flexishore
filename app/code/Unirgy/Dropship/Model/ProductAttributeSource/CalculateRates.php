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

namespace Unirgy\Dropship\Model\ProductAttributeSource;

use \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute;
use \Unirgy\Dropship\Model\Source;

class CalculateRates extends AbstractSource
{
    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var Attribute
     */
    protected $_entityAttribute;

    public function __construct(Source $source,
        Attribute $entityAttribute)
    {
        $this->_src = $source;
        $this->_entityAttribute = $entityAttribute;

    }

    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = $this->_src->setPath('product_calculate_rates')->toOptionArray();
        }
        return $this->_options;
    }

    public function getOptionArray()
    {
        $_options = array();
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    public function getFlatColums()
    {
        $columns = array();
        $columns[$this->getAttribute()->getAttributeCode()] = array(
            'type'      => 'tinyint(1)',
            'unsigned'  => false,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        return $columns;
    }

    public function getFlatIndexes()
    {
        $indexes = array();

        $index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
        $indexes[$index] = array(
            'type'      => 'index',
            'fields'    => array($this->getAttribute()->getAttributeCode())
        );

        return $indexes;
    }

    public function getFlatUpdateSelect($store)
    {
        return $this->_entityAttribute
            ->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}
