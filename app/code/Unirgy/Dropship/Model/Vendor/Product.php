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

namespace Unirgy\Dropship\Model\Vendor;

use \Magento\Catalog\Model\Product as ModelProduct;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;

class Product extends AbstractModel
{
    /**
     * @var ModelProduct
     */
    protected $_modelProduct;

    public function __construct(Context $context, 
        Registry $registry, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = [], 
        ModelProduct $modelProduct = null)
    {
        $this->_modelProduct = $modelProduct;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Unirgy\Dropship\Model\ResourceModel\Vendor\Product');
        parent::_construct();
    }

    public function getVendorCost()
    {
//        if (!$this->hasData('vendor_cost')) {
//            if ($this->getProductId()) {
//                $cost = $this->_modelProduct->load($this->getProductId())->getCost();
//                $this->setData('vendor_cost', $cost);
//            }
//        }
        return $this->getData('vendor_cost');
    }
}