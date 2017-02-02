<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class JsonProductInfo extends AbstractReview
{
    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    public function __construct(
        ProductFactory $modelProductFactory,
        Context $context,
        HelperData $helperData,
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory
    )
    {
        $this->_productFactory = $modelProductFactory;

        parent::__construct($context, $helperData, $udropshipHelper, $reviewFactory);
    }

    public function execute()
    {
        $response = new DataObject();
        $id = $this->getRequest()->getParam('id');
        if( intval($id) > 0 ) {
            $product = $this->_productFactory->create()
                ->load($id);

            $response->setId($id);
            $response->addData($product->getData());
            $response->setError(0);
        } else {
            $response->setError(1);
            $response->setMessage(__('Unable to get the product ID.'));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($response->toJSON());
    }
}
