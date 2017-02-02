<?php

namespace Unirgy\Dropship\Block\Adminhtml\SystemConfigFormField;

use \Magento\Backend\Block\Template\Context;
use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form;
use \Magento\Framework\Data\Form\Element\AbstractElement;
use \Unirgy\Dropship\Helper\Catalog;

class CategoriesSelect extends Field
{
    /**
     * @var Catalog
     */
    protected $_helperCatalog;

    protected $_hlp;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        Catalog $helperCatalog,
        Context $context,
        array $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_helperCatalog = $helperCatalog;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $value = $element->getValue();
        $cHlp = $this->_helperCatalog;
        $cOpts = $cHlp->getCategoryValues();
        if (!$value && $cHlp->getStoreRootCategory()) {
            $value = $cHlp->getStoreRootCategory()->getId();
        }
        /** @var \Magento\Framework\Data\FormFactory $formFactory */
        $formFactory = $this->_hlp->getObj('\Magento\Framework\Data\FormFactory');
        $_form = $formFactory->create();
        $_form->addType('categories_select', $this->_getTypeBlockClass());
        $catBlock = $_form->addField($element->getId(), 'categories_select', array(
            'name'=>$element->getName(),
            'label'=>__('Select Category'),
            'value'=>$value,
            'values'=>$cOpts,
            'skip_disabled'=>1
        ));
        $html = $catBlock->getElementHtml();
        return $html;
    }
    protected function _getTypeBlockClass()
    {
        return '\Unirgy\Dropship\Block\CategoriesSelect';
    }

}