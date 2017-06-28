<?php
namespace Flexishore\Search\Block;

class Search extends \Magento\Framework\View\Element\Template
{
    protected $helperData;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Flexishore\Search\Helper\Data $helperData
    ){
        parent::__construct($context);
        $this->helperData = $helperData;
    }

    public function getImage(){
        return $this->helperData->getImageFromConfig();
    }
}