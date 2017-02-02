<?php

namespace Unirgy\DropshipPayout\Controller\Paypalipn;

use Magento\Framework\App\Action\Context;
use Unirgy\DropshipPayout\Model\Method\PaypalFactory;

class Index extends AbstractPaypalipn
{
    /**
     * @var PaypalFactory
     */
    protected $_paypalFactory;

    public function __construct(
        Context $context,
        PaypalFactory $methodPaypalFactory
    )
    {
        $this->_paypalFactory = $methodPaypalFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->_paypalFactory->create()->processIpnPost();
    }
}
