<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\EntityFactory;

class Delete extends AbstractRating
{
    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        EntityFactory $ratingEntityFactory, 
        RatingFactory $modelRatingFactory)
    {
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $frameworkRegistry, $ratingEntityFactory);
    }

    public function execute()
    {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $model = $this->_ratingFactory->create();
                /* @var $model \Magento\Review\Model\Rating */
                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                $this->messageManager->addSuccess(__('The rating has been deleted.'));
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->_redirect('*/*/');
    }
}
