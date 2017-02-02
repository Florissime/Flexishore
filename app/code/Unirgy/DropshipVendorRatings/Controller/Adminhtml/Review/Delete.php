<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

class Delete extends AbstractReview
{
    public function execute()
    {
        $reviewId = $this->getRequest()->getParam('id', false);

        try {
            $this->_reviewFactory->create()->setId($reviewId)
                ->aggregate()
                ->delete();

            $this->messageManager->addSuccess(__('The review has been deleted'));
            if( $this->getRequest()->getParam('ret') == 'pending' ) {
                $this->_redirect('*/*/pending');
            } else {
                $this->_redirect('*/*/');
            }
            return;
        } catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }

        $this->_redirect('/');
    }
}
