<?php

namespace Unirgy\DropshipShippingClass\Observer;

use Magento\Framework\App\RequestInterface;
use Unirgy\DropshipShippingClass\Helper\Data as HelperData;
use Unirgy\DropshipShippingClass\Model\Source;

abstract class AbstractObserver
{
    /**
     * @var RequestInterface
     */
    protected $_appRequestInterface;

    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(
        HelperData $helperData,
        Source $modelSource,
        RequestInterface $appRequestInterface
    ) {
        $this->_helperData = $helperData;
        $this->_appRequestInterface = $appRequestInterface;

        $this->_modelSource = $modelSource;
    }
}
