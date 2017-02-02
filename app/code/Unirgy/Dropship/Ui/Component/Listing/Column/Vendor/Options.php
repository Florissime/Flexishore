<?php

namespace Unirgy\Dropship\Ui\Component\Listing\Column\Vendor;

/**
 * Class Options
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
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
            $this->options = $this->_hlp->src()->setPath('vendors')->toOptionArray();
        }
        return $this->options;
    }
}
