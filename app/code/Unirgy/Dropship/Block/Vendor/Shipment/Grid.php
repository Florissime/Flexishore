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

namespace Unirgy\Dropship\Block\Vendor\Shipment;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\View\LayoutFactory;
use \Unirgy\Dropship\Helper\Data as HelperData;

class Grid extends Template
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(
        HelperData $helperData,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $data);
    }

    protected $_collection;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($toolbar = $this->getLayout()->getBlock('shipment.grid.toolbar')) {
            $toolbar->setCollection($this->_hlp->getVendorShipmentCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }
}