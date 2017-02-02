<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;

class Tabs extends WidgetTabs
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;


    public function __construct(Context $context, 
        EncoderInterface $jsonEncoder, 
        Session $authSession, 
        Registry $frameworkRegistry,
        array $data = [])
    {
        $this->_coreRegistry = $frameworkRegistry;

        parent::__construct($context, $jsonEncoder, $authSession, $data);
        $this->setId('rating_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Rating Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', [
            'label'     => __('Rating Information'),
            'title'     => __('Rating Information'),
            'content'   => $this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tab\Form')->toHtml(),
        ])
        ;

        if( $this->_coreRegistry->registry('rating_data') ) {
            $this->addTab('answers_section', [
                    'label'     => __('Rating Options'),
                    'title'     => __('Rating Options'),
                    'content'   => $this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tab\Options')
                                    ->append($this->getLayout()->createBlock('Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tab\Options'))
                                    ->toHtml(),
               ]);
        }
        return parent::_beforeToHtml();
    }
}
