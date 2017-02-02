<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Review\Model\Rating\OptionFactory;

class Options extends Generic
{
    /**
     * @var OptionFactory
     */
    protected $_ratingOptionFactory;

    public function __construct(Context $context, 
        Registry $registry, 
        FormFactory $formFactory, 
        OptionFactory $ratingOptionFactory, 
        array $data = [])
    {
        $this->_ratingOptionFactory = $ratingOptionFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }


    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('options_form', ['legend'=>__('Assigned Options')]);

        if ($this->_coreRegistry->registry('rating_data')) {
            $collection = $this->_ratingOptionFactory->create()
                ->getResourceCollection()
                ->addRatingFilter($this->_coreRegistry->registry('rating_data')->getId())
                ->load();

            foreach( $collection->getItems() as $item ) {
                $fieldset->addField('option_code_' . $item->getId() , 'text', [
                                        'label'     => __('Option Label'),
                                        'required'  => true,
                                        'name'      => 'option_title[' . $item->getId() . ']',
                                        'value'     => $item->getCode(),
                                    ]
                );
            }
        } 

        $this->setForm($form);
        return parent::_prepareForm();
    }

}
