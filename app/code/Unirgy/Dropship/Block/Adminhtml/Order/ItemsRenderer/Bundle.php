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

namespace Unirgy\Dropship\Block\Adminhtml\Order\ItemsRenderer;

use \Magento\Bundle\Block\Adminhtml\Sales\Order\View\Items\Renderer;
use \Magento\Sales\Model\Order\Item;

class Bundle
    extends Renderer
{
    public function getItemOrderOptions($item)
    {
        $result = array();
        if ($item instanceof Item) {
            $options = $item->getProductOptions();
        } else {
            $options = $item->getOrderItem()->getProductOptions();
        }
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (!empty($options['attributes_info'])) {
                $result = array_merge($options['attributes_info'], $result);
            }
        }
        return $result;
    }
}