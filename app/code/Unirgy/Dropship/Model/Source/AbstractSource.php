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
 * @package    \Unirgy\DropshipCatalog
 * @copyright  Copyright (c) 2015-2016 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\Dropship\Model\Source;

use \Magento\Framework\DataObject;

abstract class AbstractSource extends DataObject
{
    abstract public function toOptionHash($selector=false);

    public function toOptionArray($selector=false)
    {
        $arr = array();
        foreach ($this->toOptionHash($selector) as $v=>$l) {
            if (!is_array($l)) {
                $arr[] = array('label'=>$l, 'value'=>$v);
            } else {
                $options = array();
                foreach ($l as $v1=>$l1) {
                    $options[] = array('value'=>$v1, 'label'=>$l1);
                }
                $arr[] = array('label'=>$v, 'value'=>$options);
            }
        }
        return $arr;
    }

    public function getOptionLabel($value)
    {
        $options = $this->toOptionHash();
        if (is_array($value)) {
            $result = array();
            foreach ($value as $v) {
                $result[$v] = isset($options[$v]) ? $options[$v] : $v;
            }
        } else {
            $result = isset($options[$value]) ? $options[$value] : $value;
        }
        return $result;
    }
}
