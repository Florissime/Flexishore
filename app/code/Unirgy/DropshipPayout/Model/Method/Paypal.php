<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Http\Adapter\Curl;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Vendor\Statement\StatementInterface;

class Paypal implements MethodInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Reader
     */
    protected $_dirReader;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var RequestInterface
     */
    protected $_request;

    protected $filterManager;
    protected $stringUtils;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Reader $dirReader, 
        StoreManagerInterface $storeManager,
        HelperData $udropshipHelper,
        LoggerInterface $logger,
        RequestInterface $request,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\StringUtils $stringUtils
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_dirReader = $dirReader;
        $this->_storeManager = $storeManager;
        $this->_hlp = $udropshipHelper;
        $this->_logger = $logger;
        $this->_request = $request;
        $this->filterManager = $filterManager;
        $this->stringUtils = $stringUtils;

    }

    protected $_hasExtraInfo=false;
    public function hasExtraInfo($payout)
    {
        return $this->_hasExtraInfo;
    }
    protected $_isOnline=true;
    public function isOnline()
    {
        return $this->_isOnline;
    }
    public function isTestMode()
    {
        return $this->_scopeConfig->getValue('udropship/payout_paypal/test_mode', ScopeInterface::SCOPE_STORE);
    }

    protected function _getSoapWsdl()
    {
        //return $this->_getSoapWsdlFile();
        return $this->_getSoapWsdlUrl();
    }
    protected function _getSoapWsdlFile()
    {
        return $this->_dirReader->getModuleDir('etc', 'Unirgy_DropshipPayout').DIRECTORY_SEPARATOR.'paypal'.DIRECTORY_SEPARATOR.'PayPalSvc.wsdl';
    }
    protected function _getSoapWsdlUrl()
    {
        return $this->isTestMode()
            ? 'https://www.sandbox.paypal.com/wsdl/PayPalSvc.wsdl'
            : 'https://www.paypal.com/wsdl/PayPalSvc.wsdl';
    }
    protected function _getSoapLocation()
    {
        return $this->isTestMode()
            ? 'https://api-3t.sandbox.paypal.com/2.0/'
            : 'https://api-3t.paypal.com/2.0/';
    }

    public function getSoapClient()
    {
        if ($this->isTestMode()) {
            $wsdlOptions = [
                'trace' => !!$this->isTestMode(),
            ];
        } else {
            $wsdlOptions = [
                'cache_wsdl' => WSDL_CACHE_BOTH,
                'trace'      => true,
            ];
        }
        $wsdlOptions['location'] = $this->_getSoapLocation();

        $client = new \SoapClient($this->_getSoapWsdl(), $wsdlOptions);

        return $client;
    }

    protected function _generateUniqueId()
    {
        $unq = sha1(uniqid(microtime(), true));
        return substr($unq, 0, 15).substr($unq, 25);
    }
    
    public function pay($payout)
    {
        if ($payout instanceof StatementInterface) {
            $payout = [$payout];
        }

        $ptReq = [];
        $ptToPay = [];
        foreach ($payout as $pt) {
            $pt->unsPayoutMethodError();
            $vId = $pt->getVendorId();
            if ($pt->getTotalDue()<=0) {
                $pt->setPayoutMethodErrors(['Total Due must be greater than 0']);
            } else {
                if (!isset($ptReq[$vId])) {
                    $ptReq[$vId] = [
                        'ReceiverEmail' => trim($pt->getVendor()->getPayoutPaypalEmail()),
                        'Amount' => [
                            'currencyID' => $this->_storeManager->getStore()->getBaseCurrencyCode(),
                            '_' => 0
                         ],
                         'UniqueId' => $this->_generateUniqueId(),
                         'Note' => '',
                    ];
                }
                $ptReq[$vId]['Amount']['_'] += $pt->getTotalDue();
                $ptReq[$vId]['Note'] .= ' '.$pt->getNotes();
                $ptToPay[] = $pt->getId();
            }
        }

        if (empty($ptReq)) return;
        if (count($ptReq)>=250) throw new \Exception('You can pay up to 250 recipients at once');

        foreach ($ptReq as &$ptr) {
            $ptr['Amount']['_'] = sprintf('%.2F', round($ptr['Amount']['_'], 2));
            $ptr['Note'] = $this->filterManager->truncate(
                $this->stringUtils->cleanString(preg_replace('[^\w\d ]', '', $ptr['Note'])),
                ['length' => 3999]
            );
        }
        unset($ptr);

        $soap = $this->getSoapClient();
        $soap->__setSoapHeaders(new \SoapHeader('urn:ebay:api:PayPalAPI', 'RequesterCredentials', [
            'Credentials' => [
                'Username' => $this->_scopeConfig->getValue('udropship/payout_paypal/username', ScopeInterface::SCOPE_STORE),
                'Password' => $this->_scopeConfig->getValue('udropship/payout_paypal/password', ScopeInterface::SCOPE_STORE),
                'Signature' => $this->_scopeConfig->getValue('udropship/payout_paypal/signature', ScopeInterface::SCOPE_STORE),
                //'Subject' => $this->_configScopeConfigInterface->getValue('udropship/payout_paypal/subject', ScopeInterface::SCOPE_STORE),
            ]
        ]));

        $response = $soap->MassPay([
            'MassPayRequest' => [
                'Version' => '65.1',
                'ReceiverType' => 'EmailAddress',
                'MassPayItem' => array_values($ptReq),
            ]
        ]);

        $this->_hlp->dump('REQUEST HEADERS', 'udpayout_paypal');
        $this->_hlp->dump($soap->__getLastRequestHeaders(), 'udpayout_paypal');
        $this->_hlp->dump('REQUEST', 'udpayout_paypal');
        $this->_hlp->dump($soap->__getLastRequest(), 'udpayout_paypal');
        $this->_hlp->dump('RESPONSE HEADERS', 'udpayout_paypal');
        $this->_hlp->dump($soap->__getLastResponseHeaders(), 'udpayout_paypal');
        $this->_hlp->dump('RESPONSE', 'udpayout_paypal');
        $this->_hlp->dump($soap->__getLastResponse(), 'udpayout_paypal');

        switch($response->Ack) {
            case 'Success':
            case 'SuccessWithWarning':
                foreach ($payout as $pt) {
                    if (in_array($pt->getId(), $ptToPay)) {
                        $pt->setPaypalCorrelationId($response->CorrelationID);
                        $pt->setPaypalUniqueId($ptReq[$pt->getVendorId()]['UniqueId']);
                        if (!$this->_scopeConfig->isSetFlag('udropship/payout_paypal/use_ipn', ScopeInterface::SCOPE_STORE)) {
                            $pt->afterPay();
                        } else {
                            $pt->addMessage(
                                __('Successfully send payment. Waiting for IPN to complete.'),
                                Payout::STATUS_PAYPAL_IPN
                            )
                            ->setIsJustPaid(true);
                        }
                    }
                }
                break;
            default:
                $errArr = [];
                if (is_array($response->Errors)) {
                    foreach ($response->Errors as $_err) {
                        $errArr[] = sprintf('Error %s: %s (%s)',
                            $response->Errors->ErrorCode,
                            $response->Errors->ShortMessage,
                            $response->Errors->LongMessage
                        );
                    }
                } else {
                    $errArr[] = sprintf('Error %s: %s (%s)',
                        $response->Errors->ErrorCode,
                        $response->Errors->ShortMessage,
                        $response->Errors->LongMessage
                    );
                }
                foreach ($payout as $pt) {
                    if (in_array($pt->getId(), $ptToPay)) {
                        $pt->setPayoutMethodErrors($errArr);
                    }
                }
                throw new \Exception(implode("\n", $errArr));
        }

    }

    const DEBUG_LOG = 'payout-paypal-ipn.log';
    
    protected function _getPaypalUrl()
    {
        return $this->isTestMode()
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://www.paypal.com/cgi-bin/webscr';
    }
    protected function _postBack()
    {
        $this->_logger->debug($this->_request->getRawBody());
        $httpAdapter = new Curl();
        $httpAdapter->write(\Zend_Http_Client::POST, $this->_getPaypalUrl(), '1.1', [], 'cmd=_notify-validate&'.$this->_request->getRawBody());
        $response = $httpAdapter->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        $this->_logger->debug('RESPONSE: '.substr($response, 0, 100));
        return $response;
    }
    public function processIpnPost()
    {
        $data = (array)$this->_request->getPost();
        if ($data['txn_type']!='masspay') return;
        try {
            $response = $this->_postBack();
            if ($response != 'VERIFIED') {
                throw new \Exception('Masspay PayPal IPN postback failure. See '.self::DEBUG_LOG.' for details.');
            } else {
                $i=1;
                while(isset($data['unique_id_'.$i])) {
                    if ($data['status_'.$i]!='Completed') {
                        $i++;
                        continue;
                    }
                    $ptCollection = $this->_hlp->createObj('\Unirgy\DropshipPayout\Model\ResourceModel\Payout\Collection');
                    $ptCollection->addFieldToFilter('paypal_unique_id', $data['unique_id_'.$i]);
                    foreach ($ptCollection as $pt) {
                        $pt->setTransactionId($data['masspay_txn_id_'.$i]);
                        $pt->setTransactionFee($data['mc_fee_'.$i]);
                        if ($pt->getPayoutStatus()!=Payout::STATUS_PAID) {
                            $pt->afterPay();
                        }
                        $pt->save();
                    }
                    $i++;
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
    }
}
