<?php

namespace Unirgy\Dropship\Controller\Adminhtml\Vendor;

class Save extends AbstractVendor
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ( $this->getRequest()->getPost() ) {
            $r = $this->getRequest();
            $hlp = $this->_hlp;
            try {
                $id = $r->getParam('id');
                $new = !$id;
                $data = $r->getParams();
                $data['vendor_id'] = $id;
                $data['status'] = @$data['status1'];

                $model = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
                if ($id) {
                    $model->load($id);
                }
                $hlp->processPostMultiselects($data);
                $model->addData($data);

                $shipping = array();
                if ($r->getParam('vendor_shipping')) {
                    $shipping = \Zend_Json::decode($r->getParam('vendor_shipping'));
                }
                $model->setPostedShipping($shipping);

                $products = array();
                if ($r->getParam('vendor_products')) {
                    $products = \Zend_Json::decode($r->getParam('vendor_products'));
                }
                $model->setPostedProducts($products);

                $this->_hlp->getObj('Magento\Backend\Model\Session')->setData('uvendor_edit_data', $model->getData());
                $model->save();
                $this->_hlp->getObj('Magento\Backend\Model\Session')->unsetData('uvendor_edit_data');

                $this->messageManager->addSuccess(__('Vendor was successfully saved'));

                $nonSavedMethodIds = array_diff(array_keys($shipping), array_keys($model->getNonCachedShippingMethods()));

                if (!empty($nonSavedMethodIds)) {
                    $shippingMethods = $hlp->getShippingMethods();
                    $nonSavedMethods = array();
                    foreach ($nonSavedMethodIds as $id) {
                        if (($sItem = $shippingMethods->getItemById($id))) {
                            $nonSavedMethods[$id] = $sItem->getShippingTitle();
                        }
                    }
                    if (!empty($nonSavedMethods)) {
                        $this->messageManager->addNotice(__('This shipping methods were not saved: %1. Try to use overrides.', implode(', ', $nonSavedMethods)));
                    }
                    return $resultRedirect->setPath('*/*/edit', array('id' => $model->getId(), 'tab'=>'shipping_section'));
                } else {
                    if ($r->getParam('save_continue')) {
                        return $resultRedirect->setPath('*/*/edit', array('id' => $model->getId()));
                    } else {
                        return $resultRedirect->setPath('*/*/');
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                if ($r->getParam('reg_id')) {
                    return $resultRedirect->setPath('umicrosite/registration/edit', array('reg_id'=>$r->getParam('reg_id')));
                }
                return $resultRedirect->setPath('*/*/edit', array('id' => $r->getParam('id')));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
