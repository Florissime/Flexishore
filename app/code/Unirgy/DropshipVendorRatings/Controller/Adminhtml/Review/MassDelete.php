<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Review\Model\ReviewFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class MassDelete extends AbstractReview
{
    public function execute()
    {
        $reviewsIds = $this->getRequest()->getParam('udratings');
        if(!is_array($reviewsIds)) {
             $this->messageManager->addError(__('Please select review(s).'));
        } else {
            try {
                foreach ($reviewsIds as $reviewId) {
                    $model = $this->_reviewFactory->create()->load($reviewId);
                    $model->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) have been deleted.', count($reviewsIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/' . $this->getRequest()->getParam('ret', 'index'));
    }
}
