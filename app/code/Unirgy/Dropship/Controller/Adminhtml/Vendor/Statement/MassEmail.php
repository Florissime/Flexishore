<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;


class MassEmail extends AbstractStatement
{
    public function execute()
    {
        $objIds = (array)$this->getRequest()->getParam('statement');
        if (!is_array($objIds)) {
            $this->messageManager->addError(__('Please select statement(s)'));
        }
        try {
            foreach ($objIds as $id) {
                $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')->load($id);
                $statement->send();
            }
            $this->messageManager->addSuccess(
                __('Total of %1 statement(s) have been sent', count($objIds))
            );

        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error while sending vendor statement(s): %1', $e->getMessage()));
        }

        return $this->_resultRedirectFactory->create()->setPath('*/*/');
    }
}
