<?php

namespace Unirgy\Dropship\Block\Vendor;

use \Magento\Backend\Block\Context;
use \Magento\Backend\Block\Widget\Grid\Column\Filter\Select;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\DB\Helper;
use \Magento\Framework\Url;
use \Unirgy\Dropship\Model\Source;
use \Unirgy\Dropship\Helper\Data as DropshipHelperData;

class GridColumnFilter extends Select
{
    /**
     * @var Url
     */
    protected $_modelUrl;

    /**
     * @var DropshipHelperData
     */
    protected $_hlp;

    public function __construct(
        DropshipHelperData $helperData,
        Context $context,
        Helper $resourceHelper, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context, $resourceHelper, $data);
    }

    protected function _getValues()
    {
        $values = $this->_hlp->src()->setPath('vendors')->toOptionHash(true);
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
        return $this->escapeHtml($this->getNameValue());
    }
    static protected $_gridInit = false;
    public function getHtml()
    {
        $gridId = $gridHtmlObjName = '';
        if ($this->getColumn() && $this->getColumn()->getGrid()) {
            $gridHtmlObjName = $this->getColumn()->getGrid()->getJsObjectName();
            $gridId = $this->getColumn()->getGrid()->getId();
        }
        $html = parent::getHtml();

        if ($this->_scopeConfig->isSetFlag('udropship/vendor/autocomplete_htmlselect')) {
            /* @var \Magento\Backend\Model\Url $ahlp */
            $ahlp = $this->_hlp->createObj('\Magento\Backend\Model\Url');
            $url = $ahlp->getUrl('udropship/index/vendorAutocomplete');
            $htmlId = $this->_getHtmlId();
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
<style>
#{$htmlId} {width: 200px;}
</style>
EOT;
        }

        return $html;
    }

}
