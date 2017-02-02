<?php

namespace Unirgy\Dropship\Block\Vendor;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Data\Form\Element\CollectionFactory;
use \Magento\Framework\Data\Form\Element\Factory;
use \Magento\Framework\Data\Form\Element\Select;
use \Magento\Framework\Escaper;
use \Magento\Framework\Url;
use \Unirgy\Dropship\Model\Source;

class Htmlselect extends Select
{
    protected $_src;

    protected $scopeConfig;

    protected $_url;
    protected $_layout;

    public function __construct(
        \Unirgy\Dropship\Helper\Data $udropshipHelper,
        \Magento\Framework\View\LayoutInterface $layout,
        Source $source,
        ScopeConfigInterface $scopeConfig,
        Url $url,
        Factory $factoryElement,
        CollectionFactory $factoryCollection, 
        Escaper $escaper,
        $data = []
    )
    {
        $this->_hlp = $udropshipHelper;
        $this->_layout = $layout;
        $this->_src = $source;
        $this->_scopeConfig = $scopeConfig;
        $this->_url = $url;

        $data['style'] = 'width: 250px';

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    public function getValues()
    {
        $values = $this->_src->setPath('vendors')->toOptionArray(true);
        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            $values = $this->_src->setPath('vendors')->toOptionHash(true);
            $value = $this->_getData('value');
            $name = isset($values[$value]) ? $values[$value] : $value;
            $values = [0=>[
                'value' => $this->_getData('value'),
                'label' => $name,
            ]];
        }
        return $values;
    }
    protected function _getValues()
    {
        $values = $this->_src->setPath('vendors')->toOptionHash(true);
        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            $value = $this->_getData('value');
            $name = isset($values[$value]) ? $values[$value] : $value;
            $values = [$this->_getData('value') => $name];
        }
        return $values;
    }
    public function getNameValue()
    {
        $values = $this->_getValues();
        $value = $this->_getData('value');
        return isset($values[$value]) ? $values[$value] : $value;
    }
    public function getEscapedNameValue()
    {
        return $this->_escape($this->getNameValue());
    }
    /*
    public function getElementHtml()
    {
        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            $html = $this->_layout->createBlock('\Unirgy\Dropship\Block\Vendor\Renderer\Htmlselect')
                ->setElement($this)
                ->setElementHtmlId($this->getHtmlId())
                ->setElementName($this->getName())
                ->setElementValue($this->getValue())
                ->toHtml();
            $html.= $this->getAfterElementHtml();
        } else {
            $html = parent::getElementHtml();
        }
        return $html;
    }
    */

    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();
        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            /* @var \Magento\Backend\Model\Url $ahlp */
            $ahlp = $this->_hlp->createObj('\Magento\Backend\Model\Url');
            $url = $ahlp->getUrl('udropship/index/vendorAutocomplete');
            $htmlId = $this->getHtmlId();
            $html .= <<<EOT
<script>
require(["jquery","select2","domReady!"], function($) {
$("#{$htmlId}").select2({
  ajax: {
    url: "{$url}",
    dataType: 'json',
    delay: 250,
    data: function (params) {
      return {
        q: params.term, // search term
        page: params.page
      };
    },
    processResults: function (data, params) {
      // parse the results into the format expected by Select2
      // since we are using custom formatting functions we do not need to
      // alter the remote JSON data, except to indicate that infinite
      // scrolling can be used
      params.page = params.page || 1;

      return {
        results: data.items,
        pagination: {
          more: (params.page * 30) < data.total_count
        }
      };
    },
    cache: true
  },
  escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
  minimumInputLength: 1,
  templateResult: formatRepo,
  templateSelection: formatRepoSelection
});
function formatRepo (repo) {
  if (repo.loading) return repo.text;

  var markup = "<div class='select2-result-repository clearfix'>" +
        "<div class='select2-result-repository__meta'>" +
      "<div class='select2-result-repository__title'>" + repo.full_name + "</div>"+
      "</div>" +
  "</div></div>";


  markup += "</div>" +
  "</div></div>";

  return markup;
}

function formatRepoSelection (repo) {
  return repo.full_name || repo.text;
}
});
</script>
EOT;
        }
        return $html;
    }

}
