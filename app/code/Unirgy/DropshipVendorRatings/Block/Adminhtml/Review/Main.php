<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid\Container;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;

class Main extends Container
{
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    protected $_blockGroup = 'Unirgy_DropshipVendorRatings';
    public function __construct(Context $context, 
        CustomerFactory $modelCustomerFactory, 
        Registry $frameworkRegistry, 
        array $data = [])
    {
        $this->_customerFactory = $modelCustomerFactory;
        $this->_coreRegistry = $frameworkRegistry;
        parent::__construct($context, $data);
    }
    protected function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_review';

        // lookup customer, if id is specified
        $customerId = $this->getRequest()->getParam('customerId', false);
        $customerName = '';
        if ($customerId) {
            $customer = $this->_customerFactory->create()->load($customerId);
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
        }

        $this->removeButton('add');
        if( $this->_coreRegistry->registry('usePendingFilter') === true ) {
            if ($customerName) {
                $this->_headerText = __('Pending Vendor Reviews of Customer `%1`', $customerName);
            } else {
                $this->_headerText = __('Pending Vendor Reviews');
            }
        } else {
            if ($customerName) {
                $this->_headerText = __('All Vendor Reviews of Customer `%1`', $customerName);
            } else {
                $this->_headerText = __('All Vendor Reviews');
            }
        }
    }
}
