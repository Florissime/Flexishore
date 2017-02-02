<?php

namespace Unirgy\Dropship\Model\SystemConfig\Backend;

use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Value;
use \Magento\Framework\DataObject;
use \Magento\Framework\Data\Collection\AbstractDb;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Registry;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Dropship\Helper\Label;
use \Unirgy\Dropship\Model\Vendor;

class EndiciaPass extends Value
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var Label
     */
    protected $_helperLabel;

    public function __construct(
        HelperData $helperData,
        Label $helperLabel,
        Context $context,
        Registry $registry, 
        ScopeConfigInterface $config, 
        TypeListInterface $cacheTypeList, 
        AbstractResource $resource = null, 
        AbstractDb $resourceCollection = null, 
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_helperLabel = $helperLabel;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $groups = $this->getGroups();
        $useGlobal = @$groups['general']['fields']['use_global']['value'];
        $endiciaCfg = new DataObject((array)@$groups['endicia']['fields']);
        $callEndiciaChangePass = true;
        foreach (array('endicia_requester_id', 'endicia_account_id', 'endicia_pass_phrase') as $eKey) {
            if (!$endiciaCfg->getData($eKey.'/value')) {
                $callEndiciaChangePass = false;
                break;
            }
        }
        $eNewPh = $endiciaCfg->getData('endicia_new_pass_phrase/value');
        $eNewPhC = $endiciaCfg->getData('endicia_new_pass_phrase_confirm/value');
        $callEndiciaChangePass = $callEndiciaChangePass && $eNewPh;
        if ($useGlobal && $callEndiciaChangePass) {
            if ((string)$eNewPh!=(string)$eNewPhC) {
                throw new \Exception('"Endicia New Pass Phrase" should match "Endicia Confirm New Pass Phrase"');
            }
            $vendor = $this->_hlp->createObj('\Unirgy\Dropship\Model\Vendor');
            $labelModel = $this->_hlp->getLabelCarrierInstance('usps')->setVendor($vendor);
            $this->_helperLabel->useGlobalSettings($vendor, 'usps');
            $labelModel->changePassPhrase($eNewPh);
            $this->_helperLabel->unUseGlobalSettings($vendor, 'usps');
            $this->setField('endicia_pass_phrase');
            $this->setPath(str_replace('endicia_new_pass_phrase', 'endicia_pass_phrase', $this->getPath()));
        } else {
            $this->setValue('');
        }
        return parent::beforeSave();
    }
}