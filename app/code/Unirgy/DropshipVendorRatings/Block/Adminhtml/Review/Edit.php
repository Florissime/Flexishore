<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Review;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Magento\Review\Model\ReviewFactory;

class Edit extends Container
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    protected $_blockGroup = 'Unirgy_DropshipVendorRatings';
    public function __construct(Context $context, 
        Registry $frameworkRegistry, 
        ReviewFactory $modelReviewFactory, 
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;
        $this->_reviewFactory = $modelReviewFactory;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_review';

        $this->updateButton('save', 'label', __('Save Review'));
        $this->updateButton('save', 'id', 'save_button');
        $this->updateButton('delete', 'label', __('Delete Review'));

        if( $this->getRequest()->getParam('vendorId', false) ) {
            $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('udropship/vendor/edit', ['id' => $this->getRequest()->getParam('vendorId', false)]) .'\')' );
        }

        if( $this->getRequest()->getParam('customerId', false) ) {
            $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('customer/index/edit', ['id' => $this->getRequest()->getParam('customerId', false)]) .'\')' );
        }

        if( $this->getRequest()->getParam('ret', false) == 'pending' ) {
            $this->updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/*/pending') .'\')' );
            $this->updateButton('delete', 'onclick', 'deleteConfirm(\'' . __('Are you sure you want to do this?') . '\', \'' . $this->getUrl('*/*/delete', [
                    $this->_objectId => $this->getRequest()->getParam($this->_objectId),
                    'ret'           => 'pending',
                ]) .'\')' );
            $this->_coreRegistry->register('ret', 'pending');
        }

        if( $this->getRequest()->getParam($this->_objectId) ) {
            $reviewData = $this->_reviewFactory->create()
                ->load($this->getRequest()->getParam($this->_objectId));
            $this->_coreRegistry->register('review_data', $reviewData);
        }

    }

public function getFormScripts()
{
ob_start();
?>
<script type="text/javascript">

    var deps = [];
    deps.push('prototype');
    deps.push("domReady!");
require(deps, function() {
    var review = {
        updateRating: function() {
            elements = [$("select_stores"), $("rating_detail").getElementsBySelector("input[type='radio']")].flatten();
            $('save_button').disabled = true;
            new Ajax.Updater("rating_detail", "'.$this->getUrl('udratings/review/ratingItems', ['_current'=>true]).'", {parameters:Form.serializeElements(elements), evalScripts:true, onComplete:function(){ $('save_button').disabled = false; } });
        },
        updateRatingNa: function() {
            elements = [$("select_stores"), $("rating_detail_na").getElementsBySelector("input[type='radio']")].flatten();
            $('save_button').disabled = true;
            new Ajax.Updater("rating_detail_na", "'.$this->getUrl('udratings/review/ratingItemsNa', ['_current'=>true]).'", {parameters:Form.serializeElements(elements), evalScripts:true, onComplete:function(){ $('save_button').disabled = false; } });
        }
    }
    Event.observe(window, 'load', function(){
        Event.observe($("select_stores"), 'change', review.updateRating);
    });

});
</script>
    <?php
    return ob_get_clean();
}

    public function getHeaderText()
    {
        if( $this->_coreRegistry->registry('review_data') && $this->_coreRegistry->registry('review_data')->getId() ) {
            return __("Edit Review '%1'", $this->escapeHtml($this->_coreRegistry->registry('review_data')->getTitle()));
        } else {
            return __('New Review');
        }
    }
}
