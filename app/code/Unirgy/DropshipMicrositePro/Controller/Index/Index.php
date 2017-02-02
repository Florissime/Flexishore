<?php

namespace Unirgy\DropshipMicrositePro\Controller\Index;

use Magento\Framework\App\Action\Context;
use Unirgy\DropshipMicrosite\Helper\Data as HelperData;

class Index extends AbstractIndex
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(Context $context, 
        HelperData $helperData)
    {
        $this->_helperData = $helperData;

        parent::__construct($context);
    }

    public function execute()
    {
        $vendor = $this->_helperData->getCurrentVendor();
        if ($vendor) {
            $this->_forward('landingPage');
            return;
        }
        $this->_forward('index', 'index', 'cms');
    }
}
