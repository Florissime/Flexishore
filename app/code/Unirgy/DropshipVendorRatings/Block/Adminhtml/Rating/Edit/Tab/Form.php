<?php

namespace Unirgy\DropshipVendorRatings\Block\Adminhtml\Rating\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form as DataForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store;
use Unirgy\Dropship\Model\Source;

class Form extends Generic
{
    /**
     * @var Source
     */
    protected $_src;

    /**
     * @var Store
     */
    protected $_systemStore;

    public function __construct(Context $context, 
        Registry $registry, 
        FormFactory $formFactory, 
        Source $modelSource,
        Store $systemStore, 
        array $data = [])
    {
        $this->_src = $modelSource;
        $this->_systemStore = $systemStore;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $this->setForm($form);
        $defaultStore = $this->_storeManager->getStore(0);

        $fieldset = $form->addFieldset('rating_form', [
            'legend'=>__('Rating Title')
        ]);

        $fieldset->addField('is_aggregate', 'select', [
            'name'      => 'is_aggregate',
            'label'     => __('Is Aggregatable'),
            'disabled'  => $this->_coreRegistry->registry('rating_data'),
            'options'   => $this->_src->setPath('yesno')->toOptionHash(),
        ]);

        $fieldset->addField('rating_code', 'text', [
            'name'      => 'rating_code',
            'label'     => __('Default Value'),
            'class'     => 'required-entry',
            'required'  => true,

        ]);

//        if (!$this->_modelStoreManagerInterface->isSingleStoreMode()) {
            foreach($this->_systemStore->getStoreCollection() as $store) {
                $fieldset->addField('rating_code_' . $store->getId(), 'text', [
                    'label'     => $store->getName(),
                    'name'      => 'rating_codes['. $store->getId() .']',
                ]);
            }
//        }

        if (ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->getRatingData()) {
            $form->setValues(ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->getRatingData());
            $data = ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->getRatingData();
            if (isset($data['rating_codes'])) {
               $this->_setRatingCodes($data['rating_codes']);
            }
            ObjectManager::getInstance()->get('Magento\Backend\Model\Session')->setRatingData(null);
        }
        elseif ($this->_coreRegistry->registry('rating_data')) {
            $form->setValues($this->_coreRegistry->registry('rating_data')->getData());
            if ($this->_coreRegistry->registry('rating_data')->getRatingCodes()) {
               $this->_setRatingCodes($this->_coreRegistry->registry('rating_data')->getRatingCodes());
            }
        }

        if (!$this->_coreRegistry->registry('rating_data')) {
            for ($i=0; $i<=5; $i++ ) {
                $fieldset->addField('option_code_' . $i, 'hidden', [
                    'required'  => true,
                    'name'      => 'option_title[add_' . $i . ']',
                    'value'     => $i,
                ]);
            }
        }

        $fieldset = $form->addFieldset('visibility_form', [
            'legend'    => __('Rating Visibility')]
        );
        $fieldset->addField('stores', 'multiselect', [
            'label'     => __('Visible In'),
            'name'      => 'stores[]',
            'values'    => $this->_systemStore->getStoreValuesForForm()
        ]);

        if ($this->_coreRegistry->registry('rating_data')) {
            $form->getElement('stores')->setValue($this->_coreRegistry->registry('rating_data')->getStores());
        }

        return parent::_prepareForm();
    }

    protected function _setRatingCodes($ratingCodes) {
        foreach($ratingCodes as $store=>$value) {
            if($element = $this->getForm()->getElement('rating_code_' . $store)) {
               $element->setValue($value);
            }
        }
    }

    protected function _toHtml()
    {
        return $this->_getWarningHtml() . parent::_toHtml();
    }

    protected function _getWarningHtml()
    {
        return '<div>
<ul class="messages">
    <li class="notice-msg">
        <ul>
            <li>'.__('If you do not specify a rating title for a store, the default value will be used.').'</li>
        </ul>
    </li>
</ul>
</div>';
    }


}
