<?php

namespace Unirgy\Dropship\Model\Pdf\ShipmentItems;

use \Magento\Bundle\Model\Sales\Order\Pdf\Items\AbstractItems;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Filesystem;
use \Magento\Framework\Filter\FilterManager;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Magento\Tax\Helper\Data as HelperData;

class Bundle extends AbstractItems
{
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $_strHlp;

    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $stringHelper,
        Context $context,
        Registry $registry, 
        HelperData $taxData, 
        Filesystem $filesystem, 
        FilterManager $filterManager, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_strHlp = $stringHelper;

        parent::__construct($context, $registry, $taxData, $filesystem, $filterManager, $resource, $resourceCollection, $data);
    }

    /**
     * Draw item line
     *
     */
    public function draw()
    {
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();

        $this->_setFontRegular();

        $shipItems = $this->getChilds($item);
        $items = array_merge(array($item->getOrderItem()), $item->getOrderItem()->getChildrenItems());

        $_prevOptionId = '';
        $drawItems = array();

        foreach ($shipItems as $_shipItem) {
            $_item = $_shipItem->getOrderItem();
            $line   = array();

            $attributes = $this->getSelectionAttributes($_item);
            if (is_array($attributes)) {
                $optionId   = $attributes['option_id'];
            }
            else {
                $optionId = 0;
            }

            if (!isset($drawItems[$optionId])) {
                $drawItems[$optionId] = array(
                    'lines'  => array(),
                    'height' => 15
                );
            }

            if ($_item->getParentItem()) {
                if ($_prevOptionId != $attributes['option_id']) {
                    $line[0] = array(
                        'font'  => 'italic',
                        'text'  => $this->_strHlp->split($attributes['option_label'], 60, true, true),
                        'feed'  => 60
                    );

                    $drawItems[$optionId] = array(
                        'lines'  => array($line),
                        'height' => 15
                    );

                    $line = array();

                    $_prevOptionId = $attributes['option_id'];
                }
            }

            if (($this->isShipmentSeparately() && $_item->getParentItem())
                || (!$this->isShipmentSeparately() && !$_item->getParentItem())
            ) {
                if (isset($shipItems[$_item->getId()])) {
                    $qty = $shipItems[$_item->getId()]->getQty()*1;
                } else if ($_item->getIsVirtual()) {
                    $qty = __('N/A');
                } else {
                    $qty = 0;
                }
            } else {
                $qty = '';
            }

            $line[] = array(
                'text'  => $qty,
                'feed'  => 35
            );

            // draw Name
            if ($_item->getParentItem()) {
                $feed = 65;
                $name = $this->getValueHtml($_item);
            } else {
                $feed = 60;
                $name = $_item->getName();
            }
            $text = array();
            foreach ($this->_strHlp->split($name, 60, true, true) as $part) {
                $text[] = $part;
            }
            $line[] = array(
                'text'  => $text,
                'feed'  => $feed
            );

            // draw SKUs
            $text = array();
            foreach ($this->_strHlp->split($_item->getSku(), 25) as $part) {
                $text[] = $part;
            }
            $line[] = array(
                'text'  => $text,
                'feed'  => 440
            );

            $drawItems[$optionId]['lines'][] = $line;
        }

        // custom options
        $options = $item->getOrderItem()->getProductOptions();
        if ($options) {
            if (isset($options['options'])) {
                foreach ($options['options'] as $option) {
                    $lines = array();
                    $lines[][] = array(
                        'text'  => $this->_strHlp->split(strip_tags($option['label']), 70, true, true),
                        'font'  => 'italic',
                        'feed'  => 60
                    );

                    if ($option['value']) {
                        $text = array();
                        $_printValue = isset($option['print_value'])
                            ? $option['print_value']
                            : strip_tags($option['value']);
                        $values = explode(', ', $_printValue);
                        foreach ($values as $value) {
                            foreach ($this->_strHlp->split($value, 50, true, true) as $_value) {
                                $text[] = $_value;
                            }
                        }

                        $lines[][] = array(
                            'text'  => $text,
                            'feed'  => 65
                        );
                    }

                    $drawItems[] = array(
                        'lines'  => $lines,
                        'height' => 15
                    );
                }
            }
        }

        $page = $pdf->drawLineBlocks($page, $drawItems, array('table_header' => true));
        $this->setPage($page);
    }
}
