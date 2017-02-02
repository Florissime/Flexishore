<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class MassDownload extends AbstractStatement
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $objIds = (array)$this->getRequest()->getParam('statement');
        if (!is_array($objIds)) {
            $this->messageManager->addError(__('Please select statement(s)'));
        }
        try {
            /** @var \Unirgy\Dropship\Model\Pdf\Statement $generator */
            $generator = $this->_hlp->createObj('\Unirgy\Dropship\Model\Pdf\Statement');
            $generator->before();
            foreach ($objIds as $id) {
                /** @var \Unirgy\Dropship\Model\Vendor\Statement $statement */
                $statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement');
                $statement->load($id);
                if (!$statement->getId()) {
                    continue;
                }
                $generator->addStatement($statement);
            }
            $pdf = $generator->getPdf();
            if (empty($pdf->pages)) {
                throw new \Exception(__('No statements found to print'));
            }
            $generator->insertTotalsPage()->after();
            $this->_hlp->sendDownload('statements.pdf', $pdf->render(), 'application/x-pdf');
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }
        catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was an error while download vendor statement(s): %1', $e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
