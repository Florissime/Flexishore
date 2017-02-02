<?php

namespace Unirgy\DropshipVendorRatings\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Review\Model\RatingFactory;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipVendorRatings\Helper\Data as HelperData;

class ReviewForm extends Template
{
    /**
     * @var HelperData
     */
    protected $_rateHlp;

    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    protected $_hlp;

    public function __construct(
        \Unirgy\DropshipVendorRatings\Helper\Data $udropshipHelper,
        HelperData $helperData,
        RatingFactory $modelRatingFactory, 
        Context $context,
        array $data = [])
    {
        $this->_hlp = $udropshipHelper;
        $this->_rateHlp = $helperData;
        $this->_ratingFactory = $modelRatingFactory;

        parent::__construct($context, $data);
        $this->setTemplate('Unirgy_DropshipVendorRatings::unirgy/ratings/customer/review_form.phtml');
    }

    protected function _beforeToHtml()
    {
        $data = $this->_rateHlp->fetchFormData($this->getRelEntityPkValue());
        $data = is_array($data) ? $data : [];
        $data = new DataObject($data);

        // add logged in customer name as nickname
        if (!$data->getNickname()) {
            $customer = ObjectManager::getInstance()->get('Magento\Customer\Model\Session')->getCustomer();
            if ($customer && $customer->getId()) {
                $data->setNickname($customer->getFirstname());
            }
        }
        $this->assign('data', $data);
        return parent::_beforeToHtml();
    }

    public function getAction()
    {
        $id = $this->getEntityPkValue();
        $relId = $this->getRelEntityPkValue();
        return $this->_urlBuilder->getUrl('udratings/customer/post', ['id'=>$id, 'rel_id'=>$relId]);
    }

    protected function _getRatingsCollection()
    {
        $ratingCollection = $this->_ratingFactory->create()
            ->getResourceCollection()
            ->addEntityFilter('udropship_vendor')
            ->setPositionOrder()
            ->addRatingPerStoreName($this->_storeManager->getStore()->getId())
            ->setStoreFilter($this->_storeManager->getStore()->getId());
        return $ratingCollection;
    }
    protected $_aggregateRatings;
    public function getAggregateRatings()
    {
        if (null === $this->_aggregateRatings) {
            $this->_aggregateRatings = $this->_getRatingsCollection()
                ->addFieldToFilter('is_aggregate', 1)
                ->addOptionToItems();
        }
        return $this->_aggregateRatings;
    }
    protected $_nonAggregateRatings;
    public function getNonAggregateRatings()
    {
        if (null === $this->_nonAggregateRatings) {
            $this->_nonAggregateRatings = $this->_getRatingsCollection()
                ->addFieldToFilter('is_aggregate', 0)
                ->addOptionToItems();
        }
        return $this->_nonAggregateRatings;
    }
}
