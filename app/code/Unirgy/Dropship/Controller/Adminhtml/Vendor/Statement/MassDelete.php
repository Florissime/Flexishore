<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

use \Unirgy\Dropship\Model\Vendor\Statement;

class MassDelete extends AbstractStatement
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $objIds = (array)$this->getRequest()->getParam('statement');
        if (!is_array($objIds)) {
            $this->messageManager->addError(__('Please select statement(s)'));
        }
        else {
            try {
                foreach ($objIds as $objId) {
                    /** @var \Unirgy\Dropship\Model\Vendor\Statement $obj */
                    $obj = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement');
                    $obj->load($objId)->setId($objId)->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were successfully deleted', count($objIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/index');
    }
}
