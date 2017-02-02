<?php

namespace Unirgy\DropshipPayout\Controller\Adminhtml\Payout;

use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\DropshipPayout\Model\PayoutFactory;

class Save extends AbstractPayout
{
    public function execute()
    {
        if ( $this->getRequest()->getPost() ) {
            $r = $this->getRequest();
            $hlp = $this->_payoutHlp;
            try {
                if (($id = $this->getRequest()->getParam('id')) > 0) {
                    if (($payout = $this->_payoutFactory->create()->load($id)) && $payout->getId()) {
                        $payout->setNotes($this->getRequest()->getParam('notes'));
                        if (($adjArr = $this->getRequest()->getParam('adjustment'))
                            && is_array($adjArr) && is_array($adjArr['amount'])
                        ) {
                            foreach ($adjArr['amount'] as $k => $adjAmount) {
                                if (is_numeric($adjAmount)) {
                                    $createdAdj = $payout->createAdjustment($adjAmount)
                                        ->setComment(isset($adjArr['comment'][$k]) ? $adjArr['comment'][$k] : '')
                                        ->setPoType(isset($adjArr['po_type'][$k]) ? $adjArr['po_type'][$k] : null)
                                        ->setUsername($this->_session->getUser()->getUsername())
                                        ->setPoId(isset($adjArr['po_id'][$k]) ? $adjArr['po_id'][$k] : null);
                                    $payout->addAdjustment($createdAdj);
                                }
                            }
                            $payout->finishPayout();
                        }
                        $payout->save();
                        if ($this->getRequest()->getParam('pay_flag')) {
                            $payout->pay();
                            $this->messageManager->addSuccess(__('Payout was successfully paid'));
                        }
                    } else {
                        throw new \Exception(__("Payout '%1' no longer exists", $id));
                    }
                } else {
                    $hlp->processPost();
                }
                $this->messageManager->addSuccess(__('Payout was successfully saved'));

                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                $this->_hlp->logError($e);
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->_redirect('*/*/');
    }
}
