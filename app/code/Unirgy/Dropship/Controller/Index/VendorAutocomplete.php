<?php

namespace Unirgy\Dropship\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class VendorAutocomplete extends AbstractIndex
{
    protected $resultJsonFactory;

    protected $_layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        LayoutFactory $viewLayoutFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_layout = $layout;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            $this->_layout
                ->createBlock('\Unirgy\Dropship\Block\Vendor\Renderer\Htmlselect')
                ->getSuggestedVendors($this->getRequest()->getParam('label_part'))
        );
        return $resultJson;
    }
}
