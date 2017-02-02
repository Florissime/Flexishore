<?php

namespace Unirgy\DropshipMicrosite\Controller\Index;

use Magento\Framework\App\Action\Context;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class Index extends AbstractIndex
{
    /**
     * @var HelperData
     */
    protected $_msHlp;

    public function __construct(
        Context $context,
        HelperData $helperData
    )
    {
        $this->_msHlp = $helperData;

        parent::__construct($context);
    }

    public function execute()
    {
        $vendor = $this->_msHlp->getCurrentVendor();
        if ($vendor) {
            $this->_forward('defaultIndex');
            return;
        }
        $this->_forward('index', 'index', 'cms');
    }
}
