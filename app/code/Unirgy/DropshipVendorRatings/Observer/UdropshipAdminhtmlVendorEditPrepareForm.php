<?php

namespace Unirgy\DropshipVendorRatings\Observer;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as DropshipVendorRatingsHelperData;
use Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source;

class UdropshipAdminhtmlVendorEditPrepareForm extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Source
     */
    protected $_src;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $helperData,
        ScopeConfigInterface $configScopeConfigInterface,
        CustomerFactory $modelCustomerFactory,
        DropshipVendorRatingsHelperData $dropshipVendorRatingsHelperData,
        Source $modelSource
    )
    {
        $this->_src = $modelSource;

        parent::__construct($helperData, $configScopeConfigInterface, $modelCustomerFactory, $dropshipVendorRatingsHelperData);
    }

    public function execute(Observer $observer)
    {
        $id = $observer->getEvent()->getId();
        $form = $observer->getEvent()->getForm();
        $fieldset = $form->getElement('vendor_form');
        $fieldset->addField('allow_udratings', 'select', [
            'name'      => 'allow_udratings',
            'label'     => __('Allow customers review/rate vendor'),
            'options'   => $this->_src->setPath('yesno')->toOptionHash(),
        ]);
    }
}
