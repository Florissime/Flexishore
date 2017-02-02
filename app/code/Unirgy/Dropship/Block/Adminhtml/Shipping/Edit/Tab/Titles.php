<?php


namespace Unirgy\Dropship\Block\Adminhtml\Shipping\Edit\Tab;

use \Magento\Backend\Block\Widget\Form;
use \Magento\Framework\Data\Form as DataForm;

class Titles extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected function _prepareForm()
    {
        $shipping = $this->_coreRegistry->registry('shipping_data');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('shipping_');

        $fieldset = $form->addFieldset('default_title_fieldset', array(
            'legend' => __('Default Title')
        ));
        $titles = $shipping ? $shipping->getStoreTitles() : array();
        $fieldset->addField('store_default_title', 'text', array(
            'name'      => 'store_titles[0]',
            'required'  => false,
            'label'     => __('Default Title for All Store Views'),
            'value'     => isset($titles[0]) ? $titles[0] : '',
        ));

        $fieldset = $form->addFieldset('store_titles_fieldset', array(
            'legend'       => __('Store View Specific Title'),
            'table_class'  => 'form-list stores-tree',
        ));
        $renderer = $this->getLayout()->createBlock('Unirgy\Dropship\Block\Adminhtml\StoreSwitcher\FormRenderer\Fieldset');
        $fieldset->setRenderer($renderer);

        foreach ($this->_storeManager->getWebsites() as $website) {
            $fieldset->addField("w_{$website->getId()}_title", 'note', array(
                'label'    => $website->getName(),
                'fieldset_html_class' => 'website',
            ));
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                if (count($stores) == 0) {
                    continue;
                }
                $fieldset->addField("sg_{$group->getId()}_title", 'note', array(
                    'label'    => $group->getName(),
                    'fieldset_html_class' => 'store-group',
                ));
                foreach ($stores as $store) {
                    $fieldset->addField("s_{$store->getId()}", 'text', array(
                        'name'      => 'store_titles['.$store->getId().']',
                        'required'  => false,
                        'label'     => $store->getName(),
                        'value'     => isset($titles[$store->getId()]) ? $titles[$store->getId()] : '',
                        'fieldset_html_class' => 'store',
                    ));
                }
            }
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Titles');
    }
    public function getTabTitle()
    {
        return __('Titles');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }
}
