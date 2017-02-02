<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use \Magento\Backend\App\Action;

class VendorAutocomplete extends Action
{
    protected $_collectionFactory;
    protected $resultJsonFactory;
    protected $_layout;
    protected $_resourceHelper;
    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Framework\View\LayoutInterface $layout,
        \Unirgy\Dropship\Model\ResourceModel\Vendor\CollectionFactory $collectionFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        LayoutFactory $viewLayoutFactory
    ) {
        $this->_hlp = $udropshipHelper;
        $this->_resourceHelper = $resourceHelper;
        $this->_collectionFactory = $collectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_layout = $layout;

        parent::__construct($context);
    }

    public function execute()
    {
        $q = $this->getRequest()->getParam('q');
        $page = $this->getRequest()->getParam('page',1);
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(
            $this->getSuggestedVendors($q, $page)
        );
        return $resultJson;
    }
    public function getSuggestedVendors($labelPart, $page)
    {
        $escapedLabelPart = $this->_resourceHelper->addLikeEscape(
            $labelPart,
            ['position' => 'any']
        );
        $collection = $this->_collectionFactory->create()->addFieldToFilter(
            'vendor_name',
            ['like' => $escapedLabelPart]
        );

        $collection->setPageSize(20)->setCurPage($page);

        $result = [
            "total_count" => $collection->getSize(),
            "incomplete_results" => $collection->getLastPageNumber()==$page,
            "items" => []
        ];
        foreach ($collection->getItems() as $vendor) {
            $result['items'][] = [
                'id' => $vendor->getId(),
                'name' => $vendor->getVendorName(),
                'full_name' => $vendor->getVendorName(),
            ];
        }
        return $result;
    }
}
