<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Shipping;


class Save extends AbstractShipping
{
    public function execute()
    {
        $hlp = $this->_hlp;
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ( $this->getRequest()->getPost() ) {
            try {
                $r = $this->getRequest();
                $id = $r->getParam('id');
                $new = !$id;

                $postedCount = 0;
                $hasWildcard = false;
                foreach ($r->getParam('system_methods') as $carrier => $method) {
                    if (!empty($method)) {
                        $postedCount++;
                        if ($method == '*') {
                            $hasWildcard = true;
                        }
                    }
                }
                if ($postedCount>1 && $hasWildcard) {
                    throw new \Exception(
                        __('Only one system method could be selected when using "* Any available" wildcard method')
                    );
                }

                $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Shipping')
                    ->setId($id)
                    ->setShippingCode($r->getParam('shipping_code'))
                    ->setShippingTitle($r->getParam('shipping_title'))
                    ->setDaysInTransit($r->getParam('days_in_transit'))
                    ->setWebsiteIds($r->getParam('website_ids'))
                    ->setStoreTitles($r->getParam('store_titles'))
                    ->setPostedSystemMethods($r->getParam('system_methods'));

                $model->save();

                $this->messageManager->addSuccess(__('Shipping Method was successfully saved'));

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
