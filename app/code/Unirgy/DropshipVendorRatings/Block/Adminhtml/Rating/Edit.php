<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Magento\Review\Model\RatingFactory;

class Edit extends Container
{
    /**
     * @var RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    protected $_blockGroup = 'Unirgy_DropshipVendorRatings';
    public function __construct(Context $context, 
        RatingFactory $modelRatingFactory, 
        Registry $frameworkRegistry, 
        array $data = [])
    {
        $this->_ratingFactory = $modelRatingFactory;
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($context, $data);
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_rating';

    }

    protected function _construct()
    {
        parent::_construct();
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_rating';

        $this->updateButton('save', 'label', __('Save Rating'));
        $this->updateButton('delete', 'label', __('Delete Rating'));

        if( $this->getRequest()->getParam($this->_objectId) ) {

            $ratingData = $this->_ratingFactory->create()
                ->load($this->getRequest()->getParam($this->_objectId));

            $this->_coreRegistry->register('rating_data', $ratingData);
        }


    }

    public function getHeaderText()
    {
        if( $this->_coreRegistry->registry('rating_data') && $this->_coreRegistry->registry('rating_data')->getId() ) {
            return __("Edit Rating", $this->escapeHtml($this->_coreRegistry->registry('rating_data')->getRatingCode()));
        } else {
            return __('New Rating');
        }
    }
}
