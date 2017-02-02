<?php

namespace Unirgy\Dropship\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Sales\Model\Order\Shipment\Track;
use \Unirgy\DropshipManualLabel\Model\ManualLabel\Track as ManualLabelTrack;
use \Unirgy\Dropship\Helper\Data as HelperData;
use \Unirgy\Rma\Model\Rma\Track as RmaTrack;

class Label extends AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    public function __construct(Context $context,
        HelperData $helperData
    )
    {
        $this->_hlp = $helperData;

        parent::__construct($context);
    }

    protected $_labelCfgKeys = array(
        'poll_tracking',
        'label_type',
        'dimension_units',
        'default_pkg_length',
        'default_pkg_width',
        'default_pkg_height',
    );
    protected $_eplCfgKeys = array(
        'epl_doctab'
    );
    protected $_pdfCfgKeys = array(
        'pdf_page_size',
        'pdf_page_width',
        'pdf_page_height',
        'pdf_label_rotate',
        'pdf_label_left',
        'pdf_label_top',
    );
    protected $_fedexCfgKeys = array(
        'fedex_test_mode',
        'fedex_user_key',
        'fedex_user_password',
        'fedex_account_number',
        'fedex_meter_number',
        'fedex_smartpost_hubid',
        'fedex_payment_type',
        'fedex_thirdparty_account_number',
        'fedex_thirdparty_name',
        'fedex_thirdparty_company',
        'fedex_thirdparty_phone',
        'fedex_dry_ice_weight',
        'fedex_dropoff_type',
        'fedex_label_stock_type',
        'fedex_pdf_label_width',
        'fedex_pdf_label_height',
        'fedex_signature_option',
        'fedex_insurance',
        'fedex_notify_on',
        'fedex_notify_email',
        'fedex_itn',
    );
    protected $_endiciaCfgKeys = array(
        'endicia_api_url',
        'endicia_test_mode',
        'endicia_requester_id',
        'endicia_account_id',
        'endicia_pass_phrase',
        'endicia_new_pass_phrase',
        'endicia_new_pass_phrase_confirm',
        'endicia_label_type',
        'endicia_mail_class',
        'endicia_mailpiece_shape',
        'endicia_stealth',
        'endicia_delivery_confirmation',
        'endicia_signature_confirmation',
        'endicia_return_receipt',
        'endicia_electronic_return_receipt',
        'endicia_insured_mail',
        'endicia_restricted_delivery',
        'endicia_cod',
        'endicia_balance_threshold',
        'endicia_recredit_amount',
        'endicia_pdf_label_width',
        'endicia_pdf_label_height',
    );
    protected $_upsCfgKeys = array(
        'ups_api_url',
        'ups_shipper_number',
        'ups_thirdparty_account_number',
        'ups_thirdparty_country',
        'ups_thirdparty_postcode',
        'ups_insurance',
        'ups_delivery_confirmation',
        'ups_verbal_confirmation',
        'ups_pickup',
        'ups_container',
        'ups_dest_type',
        'ups_pdf_label_width',
        'ups_pdf_label_height',
    );
    public function beforeShipmentLabel($vendor, $track)
    {
        $shipment = $track;
        if ($track instanceof Track
            || $track instanceof RmaTrack
            || $track instanceof ManualLabelTrack
        ) {
            $shipment = $track->getShipment();
            if ($track->getCarrierCode()) {
                $carrierCode = $track->getCarrierCode();
            }
        }
        $storeId = $shipment->getOrder()->getStoreId();
        $vendor = $this->_hlp->getVendor($vendor);
        $method = explode('_', $shipment->getUdropshipMethod(), 2);
        if (!isset($carrierCode)) {
            $carrierCode = !empty($method[0]) ? $method[0] : $vendor->getCarrierCode();
        }
        $vendor->usePdfCarrierCode($carrierCode);
        if (!$this->_hlp->getScopeFlag('udropship_label/general/use_global')) return $this;
        $this->useGlobalSettings($vendor, $carrierCode, $storeId);
        return $this;
    }

    public function useGlobalSettings($vendor, $carrierCode, $storeId=null)
    {
        $shipmentLabel = $this->_hlp->getScopeConfig('udropship_label/label', $storeId);
        foreach ($this->_labelCfgKeys as $cfgKey) {
            $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
            $vendor->setData($cfgKey, @$shipmentLabel[$cfgKey]);
        }
        $shipmentPdf = $this->_hlp->getScopeConfig('udropship_label/pdf', $storeId);
        foreach ($this->_pdfCfgKeys as $cfgKey) {
            $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
            $vendor->setData($cfgKey, @$shipmentPdf[$cfgKey]);
        }
        $shipmentEpl = $this->erData('udropship_label/epl', $storeId);
        foreach ($this->_eplCfgKeys as $cfgKey) {
            $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
            $vendor->setData($cfgKey, @$shipmentEpl[$cfgKey]);
        }
        if ($carrierCode == 'fedex') {
            $shipmentFedex = $this->_hlp->getScopeConfig('udropship_label/fedex', $storeId);
            foreach ($this->_fedexCfgKeys as $cfgKey) {
                $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
                if ($cfgKey == 'fedex_notify_on') {
                    if (empty($shipmentFedex[$cfgKey])) {
                        $shipmentFedex[$cfgKey] = array();
                    } else {
                        if (is_scalar($shipmentFedex[$cfgKey])) {
                            $shipmentFedex[$cfgKey] = array_filter(explode(',', $shipmentFedex[$cfgKey]));
                        }
                        if (!is_array($shipmentFedex[$cfgKey])) {
                            $shipmentFedex[$cfgKey] = array();
                        }
                    }
                }
                $vendor->setData($cfgKey, @$shipmentFedex[$cfgKey]);
            }
        } elseif ($carrierCode == 'usps') {
            $shipmentEndicia = $this->_hlp->getScopeConfig('udropship_label/endicia', $storeId);
            foreach ($this->_endiciaCfgKeys as $cfgKey) {
                $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
                $vendor->setData($cfgKey, @$shipmentEndicia[$cfgKey]);
            }
        } elseif ($carrierCode == 'ups') {
            $shipmentUps = $this->_hlp->getScopeConfig('udropship_label/ups', $storeId);
            foreach ($this->_upsCfgKeys as $cfgKey) {
                $vendor->setData('__'.$cfgKey, $vendor->getData($cfgKey));
                $vendor->setData($cfgKey, @$shipmentUps[$cfgKey]);
            }
        }
    }

    public function afterShipmentLabel($vendor, $track)
    {
        $shipment = $track;
        if ($track instanceof Track
            || $track instanceof RmaTrack
            || $track instanceof ManualLabelTrack
        ) {
            $shipment = $track->getShipment();
            if ($track->getCarrierCode()) {
                $carrierCode = $track->getCarrierCode();
            }
        }
        $storeId = $shipment->getOrder()->getStoreId();
        $vendor->resetPdfCarrierCode();
        if (!$this->_hlp->getScopeFlag('udropship_label/general/use_global')) return $this;
        $method = explode('_', $shipment->getUdropshipMethod(), 2);
        $vendor = $this->_hlp->getVendor($vendor);
        if (!isset($carrierCode)) {
            $carrierCode = !empty($method[0]) ? $method[0] : $vendor->getCarrierCode();
        }
        $this->unUseGlobalSettings($vendor, $carrierCode);
        return $this;
    }
    public function unUseGlobalSettings($vendor, $carrierCode)
    {
        foreach ($this->_labelCfgKeys as $cfgKey) {
            $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
        }
        foreach ($this->_pdfCfgKeys as $cfgKey) {
            $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
        }
        foreach ($this->_eplCfgKeys as $cfgKey) {
            $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
        }
        if ($carrierCode == 'fedex') {
            foreach ($this->_fedexCfgKeys as $cfgKey) {
                $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
            }
        } elseif ($carrierCode == 'usps') {
            foreach ($this->_endiciaCfgKeys as $cfgKey) {
                $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
            }
        } elseif ($carrierCode == 'ups') {
            foreach ($this->_upsCfgKeys as $cfgKey) {
                $vendor->setData($cfgKey, $vendor->getData('__'.$cfgKey));
            }
        }
    }
}