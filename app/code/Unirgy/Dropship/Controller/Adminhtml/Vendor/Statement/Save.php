<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor\Statement;

class Save extends AbstractStatement
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ( $this->getRequest()->getPost() ) {
            $r = $this->getRequest();
            $hlp = $this->_hlp;
            /** @var \Magento\Backend\Model\Auth $auth */
            $auth = $this->_hlp->getObj('\Magento\Backend\Model\Auth');
            $adminUser = $auth->getUser();
            try {
                if (($id = $this->getRequest()->getParam('id')) > 0
                    && ($statement = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor\Statement')->load($id))
                    && $statement->getId()
                ) {
                    $statement->setNotes($this->getRequest()->getParam('notes'));
                    if (($adjArr = $this->getRequest()->getParam('adjustment'))
                        && is_array($adjArr) && is_array($adjArr['amount'])
                    ) {
                        foreach ($adjArr['amount'] as $k => $adjAmount) {
                            if (is_numeric($adjAmount)) {
                                $createdAdj = $statement->createAdjustment($adjAmount)
                                    ->setComment(isset($adjArr['comment'][$k]) ? $adjArr['comment'][$k] : '')
                                    ->setPoType(isset($adjArr['po_type'][$k]) ? $adjArr['po_type'][$k] : null)
                                    ->setUsername($adminUser->getUsername())
                                    ->setPoId(isset($adjArr['po_id'][$k]) ? $adjArr['po_id'][$k] : null);
                                $statement->addAdjustment($createdAdj);
                            }
                        }
                        $statement->finishStatement();
                    }
                     
                    $statement->save();
                    if ($this->getRequest()->getParam('refresh_flag')) {
                        $statement->fetchOrders()->save();
                        $this->messageManager->addSuccess(__('Statement was successfully refreshed'));
                    }
                    if ($this->getRequest()->getParam('pay_flag')) {
                        return $resultRedirect->setPath('udpayout/payout/edit', array('id'=>$statement->createPayout()->save()->getId()));
                    }
                } else {
                    throw new \Exception(__("Statement '%1' no longer exists", $id));
                }
                $this->messageManager->addSuccess(__('Statement was successfully saved'));

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
