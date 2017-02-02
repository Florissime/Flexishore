<?php

namespace Unirgy\DropshipVacation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UdropshipAdminhtmlVendorEditPrepareForm extends AbstractObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $form = $observer->getForm();
        $vForm = $form->getElement('vendor_form');
        if ($vForm) {
            $hlp = $this->_vacHlp;
            $vForm->addType('vacation_mode', '\Unirgy\Dropship\Block\Adminhtml\Vendor\Helper\Form\DependSelect');
            $vForm->addField('vacation_mode', 'vacation_mode', [
                'name'      => 'vacation_mode',
                'label'     => __('Vacation Mode'),
                'options'   => $this->_hlp->getObj('\Unirgy\DropshipVacation\Model\Source')->setPath('vacation_mode')->toOptionHash(),
                'field_config' => [
                    'depend_fields' => [
                        'vacation_end' => '1,2',
                    ]
                ]
            ]);
            $vForm->addField('vacation_end', 'date', [
                'name'      => 'vacation_end',
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'label'     => __('Vacation Ends At'),
            ]);
        }
    }
}
