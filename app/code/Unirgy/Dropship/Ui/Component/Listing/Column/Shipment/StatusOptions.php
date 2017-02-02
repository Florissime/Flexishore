<?php

namespace Unirgy\Dropship\Ui\Component\Listing\Column\Shipment;

/**
 * Class Options
 */
class StatusOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Unirgy\Dropship\Helper\Data
     */
    protected $_hlp;

    public function __construct(\Unirgy\Dropship\Helper\Data $udropshipHelper)
    {
        $this->_hlp = $udropshipHelper;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = $this->_hlp->src()->setPath('shipment_statuses')->toOptionArray();
        }
        return $this->options;
    }
}
