<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception;
use Magento\Review\Model\ReviewFactory;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class MassUpdateStatus extends AbstractReview
{
    public function execute()
    {
        $reviewsIds = $this->getRequest()->getParam('udratings');
        if(!is_array($reviewsIds)) {
             $this->messageManager->addError(__('Please select review(s).'));
        } else {
            try {
                $status = $this->getRequest()->getParam('status');
                foreach ($reviewsIds as $reviewId) {
                    $model = $this->_reviewFactory->create()->load($reviewId);
                    $model->setStatusId($status)
                        ->save()
                        ->aggregate();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) have been updated.', count($reviewsIds))
                );
            }
            catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            }
            catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred while updating the selected review(s).'));
            }
        }

        $this->_redirect('*/*/' . $this->getRequest()->getParam('ret', 'index'));
    }
}
