<?php

namespace Unirgy\DropshipVendorRatings\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Layout;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection;

class Data extends AbstractHelper
{
    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Layout
     */
    protected $_viewLayout;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Context $context,
        ReviewFactory $modelReviewFactory,
        StoreManagerInterface $modelStoreManagerInterface, 
        Layout $viewLayout
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_reviewFactory = $modelReviewFactory;
        $this->_storeManager = $modelStoreManagerInterface;
        $this->_viewLayout = $viewLayout;

        parent::__construct($context);
    }

    protected $_myEt = 10;
    protected $_oldEntityType = 1;
    protected $_entityType = 1;
    public function myEt()
    {
        return $this->_myEt;
    }
    public function useMyEt()
    {
        return $this->useEt($this->_myEt);
    }
    public function useEt($id=null)
    {
        $result = $this->_entityType;
        if (!is_null($id) && $this->_entityType!=$id) {
            $this->_oldEntityType = $this->_entityType;
            $this->_entityType = $id;
        }
        return $result;
    }
    public function resetEt()
    {
        $this->_entityType = $this->_oldEntityType;
        return $this;
    }

    /**
     * @return \Unirgy\Dropship\Helper\Data
     */
    public function udHlp()
    {
        return ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data');
    }

    public function getVendorReviewsCollection($vendor)
    {
        $vendor = $this->udHlp()->getVendor($vendor);
        return $this->_reviewFactory->create()->getCollection()
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->addStatusFilter(__('Approved'))
            ->addEntityFilter($this->myEt(), $vendor->getId())
            ->setFlag('AddRateVotes', true)
            ->setFlag('AddAddressData', true);
    }
    public function getCustomerReviewsCollection()
    {
        return $this->_reviewFactory->create()->getCollection()
            ->addStoreFilter($this->_storeManager->getStore()->getId())
            ->addStatusFilter(__('Approved'))
            ->addFieldToFilter('main_table.entity_id', $this->myEt())
            ->addCustomerFilter(ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomerId())
            ->setDateOrder()
            ->addRateVotes();
    }
    public function getPendingCustomerReviewsCollection()
    {
        $col = $this->udHlp()->createObj('\Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection')
            ->addCustomerFilter(ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomerId())
            ->addPendingFilter();
        return $col;
    }
    public function saveFormData($data=null, $id=null)
    {
        $formData = ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session')->getFormData();
        if (!is_array($formData)) {
            $formData = [];
        }
        $data = !is_null($data) ? $data : $this->_request->getPost();
        $id = !is_null($id) ? $id : $this->_request->getParam('rel_id');
        $formData[$id] = $data;
        ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session')->setFormData($formData);
    }

    public function fetchFormData($id=null)
    {
        $formData = ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session')->getFormData();
        if (!is_array($formData)) {
            $formData = [];
        }
        $id = !is_null($id) ? $id : $this->_request->getParam('rel_id');
        $result = false;
        if (isset($formData[$id]) && is_array($formData[$id])) {
            $result = $formData[$id];
            unset($formData[$id]);
            if (empty($formData)) {
                ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session')->getFormData(true);
            } else {
                ObjectManager::getInstance()->get('Unirgy\DropshipVendorRatings\Model\Session')->setFormData($formData);
            }
        }
        return $result;
    }
    public function getAggregateRatings()
    {
        return $this->getAggregateRatings();
    }
    public function getNonAggregateRatings()
    {
        return $this->getNonAggregateRatings();
    }
    public function getReviewsSummaryHtml($vendor, $templateType = false, $displayIfNoReviews = false)
    {
        $this->_initReviewsHelperBlock();
        return $this->_reviewsHelperBlock->getSummaryHtml($vendor, $templateType, $displayIfNoReviews);
    }
    public function addReviewSummaryTemplate($type, $template)
    {
        $this->_initReviewsHelperBlock();
        $this->_reviewsHelperBlock->addTemplate($type, $template);
    }
    protected $_reviewsHelperBlock;
    protected function _initReviewsHelperBlock()
    {
        if (!$this->_reviewsHelperBlock) {
            $this->_reviewsHelperBlock = $this->_viewLayout->createBlock('Unirgy\DropshipVendorRatings\Block\Vendor');
        }
    }

    public function sendPendingReviewEmail($customer)
    {
        $store = $this->_storeManager->getDefaultStoreView();
        $this->inlineTranslation->suspend();
        $shipments = $this->udHlp()
            ->createObj('\Unirgy\DropshipVendorRatings\Model\ResourceModel\Review\Shipment\Collection')
            ->addCustomerFilter($customer)
            ->addPendingFilter();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->udHlp()->getScopeConfig('udropship/vendor_rating/customer_email_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store' => $store,
                'store_name' => $store->getName(),
                'customer' => $customer,
                'shipments' => $shipments
            ]
        )->setFrom(
            $this->udHlp()->getScopeConfig('sales_email/shipment/identity', $store)
        )->addTo(
            $customer->getEmail(),
            $customer->getName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        foreach ($shipments as $shipment) {
            $shipment->setData('udrating_emails_sent', $shipment->getData('udrating_emails_sent')+1);
            $shipment->getResource()->saveAttribute($shipment, 'udrating_emails_sent');
        }
    }

    public function getCaseSql($valueName, $casesResults, $defaultValue = null)
    {
        $expression = 'CASE ' . $valueName;
        foreach ($casesResults as $case => $result) {
            $expression .= ' WHEN ' . $case . ' THEN ' . $result;
        }
        if ($defaultValue !== null) {
            $expression .= ' ELSE ' . $defaultValue;
        }
        $expression .= ' END';

        return new \Zend_Db_Expr($expression);
    }

}