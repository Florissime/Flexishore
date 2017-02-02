<?php

namespace Unirgy\DropshipMicrositePro\Helper;

use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Unirgy\DropshipMicrositePro\Helper\Data as DropshipMicrositeProHelperData;
use Unirgy\DropshipMicrositePro\Model\Source;
use Unirgy\Dropship\Helper\Data as HelperData;
use Zend\Json\Json;

class Data extends AbstractHelper
{
    /**
     * @var DesignInterface
     */
    protected $_viewDesign;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var TemplateFactory
     */
    protected $_modelTemplateFactory;

    /**
     * @var DropshipMicrositeProHelperData
     */
    protected $_mspHlp;

    protected $inlineTranslation;
    protected $_transportBuilder;

    public function __construct(
        \Unirgy\Dropship\Model\EmailTransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Context $context,
        DesignInterface $viewDesign,
        StoreManagerInterface $storeManager,
        HelperData $helperData, 
        TemplateFactory $modelTemplateFactory, 
        ProtectedCode $micrositeProHelperProtected
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_viewDesign = $viewDesign;
        $this->_storeManager = $storeManager;
        $this->_hlp = $helperData;
        $this->_modelTemplateFactory = $modelTemplateFactory;
        $this->_mspHlpPr = $micrositeProHelperProtected;

        parent::__construct($context);
    }

    public function checkEmailUnique($email)
    {
        if (empty($email)) {
            return false;
        } else {
            $res = $this->_hlp->rHlp();
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship_vendor'), ['count(*)'])
                    ->where('email=?', $email)
            );
            $count = $count || $read->fetchOne(
                $read->select()->from($res->getTableName('udropship_vendor_registration'), ['count(*)'])
                    ->where('email=?', $email)
            );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function checkVendorNameUnique($vendor_name)
    {
        if (empty($vendor_name)) {
            return false;
        } else {
            $res = $this->_hlp->rHlp();
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship_vendor'), ['count(*)'])
                    ->where('vendor_name=?', $vendor_name)
            );
            $count = $count || $read->fetchOne(
                $read->select()->from($res->getTableName('udropship_vendor_registration'), ['count(*)'])
                    ->where('vendor_name=?', $vendor_name)
            );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function checkUrlkeyUnique($urlkey)
    {
        if (empty($urlkey)) {
            return false;
        } else {
            $res = $this->_hlp->rHlp();
            $read = $res->getConnection('udropship_read');
            $count = $read->fetchOne(
                $read->select()->from($res->getTableName('udropship_vendor'), ['count(*)'])
                    ->where('url_key=?', $urlkey)
            );
            $count = $count || $read->fetchOne(
                    $read->select()->from($res->getTableName('udropship_vendor_registration'), ['count(*)'])
                        ->where('url_key=?', $urlkey)
                );
            if ($count) {
                return false;
            } else {
                return true;
            }
        }
    }
    public function processRandomPattern($pattern)
    {
        return preg_replace_callback('#\[([AN]{1,2})\*([0-9]+)\]#', [$this, 'convertPattern'], $pattern);
    }
    public function convertPattern($m)
    {
        $chars = (strpos($m[1], 'A')!==false ? 'ABCDEFGHJKLMNPQRSTUVWXYZ' : '').
            (strpos($m[1], 'N')!==false ? '23456789' : '');
        // no confusing chars, like O/0, 1/I
        /** @var \Magento\Framework\Math\Random $randObj */
        $randObj = $this->_hlp->getObj('\Magento\Framework\Math\Random');
        return $randObj->getRandomString($m[2], $chars);
    }
    public function getRegistrationFieldsConfig()
    {
        $regFieldsConfig = $this->_hlp->getObj('\Unirgy\DropshipMicrositePro\Model\Source')->getVendorPreferences(false);
        $fields = $this->_hlp->config()->getField();
        if (!array_key_exists('comments', $fields)) {
            $regFieldsConfig['vendor_info']['value'][] = [
                'position' => 99999,
                'label' => 'Comments',
                'value' => 'comments',
            ];
        }
        return $regFieldsConfig;
    }

    public function sendVendorConfirmationEmail($vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();

        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_hlp->getScopeConfig('udropship/microsite/confirmation_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            ]
        )->setFrom(
            $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
        )->addTo(
            $vendor->getEmail(),
            $vendor->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }

    public function sendVendorRejectEmail($vendor)
    {
        $store = $this->_storeManager->getDefaultStoreView();

        $this->inlineTranslation->suspend();

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_hlp->getScopeConfig('udropship/microsite/reject_template', $store)
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $store->getId(),
            ]
        )->setTemplateVars(
            [
                'store_name' => $store->getName(),
                'vendor' => $vendor,
            ]
        )->setFrom(
            $this->_hlp->getScopeConfig('udropship/vendor/vendor_email_identity', $store)
        )->addTo(
            $vendor->getEmail(),
            $vendor->getVendorName()
        );

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();

        return $this;
    }

    public function isPassFieldInForm()
    {
        $regFields = $this->getRegFields();
        return array_key_exists('password', $regFields);
    }
    protected $_regFields;
    public function getRegFields()
    {
        if (null === $this->_regFields) {
            $this->_regFields = [];
            $columnsConfig = $this->_hlp->getScopeConfig('udsignup/form/fieldsets');
            if (!is_array($columnsConfig)) {
                $columnsConfig = $this->_hlp->unserialize($columnsConfig);
                if (is_array($columnsConfig)) {
                foreach ($columnsConfig as $fsConfig) {
                if (is_array($fsConfig)) {
                    foreach (['top_columns','bottom_columns','left_columns','right_columns'] as $colKey) {
                    if (isset($fsConfig[$colKey]) && is_array($fsConfig[$colKey])) {
                        $requiredFields = (array)@$fsConfig['required_fields'];
                        foreach ($fsConfig[$colKey] as $fieldCode) {
                            $field = $this->_mspHlpPr->getRegistrationField($fieldCode);
                            if (!empty($field)) {
                                if (in_array($fieldCode, $requiredFields)) {
                                    $field['required'] = true;
                                } else {
                                    $field['required'] = false;
                                    if (!empty($field['class'])) {
                                        $field['class'] = str_replace('required-entry', '', $field['class']);
                                    }
                                }
                                $this->_regFields[$fieldCode] = $field;
                            }
                        }
                    }}
                }}}
            }
        }
        return $this->_regFields;
    }

    public function serialize($value)
    {
        return Json::encode($value);
    }
    public function unserialize($value)
    {
        if (empty($value)) {
            $value = empty($value) ? [] : $value;
        } elseif (!is_array($value)) {
            if (strpos($value, 'a:')===0) {
                $value = @unserialize($value);
            } elseif (strpos($value, '{')===0 || strpos($value, '[{')===0) {
                $value = Json::decode($value);
            }
        }
        return $value;
    }

}
