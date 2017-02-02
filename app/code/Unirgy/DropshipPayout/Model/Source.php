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

namespace Unirgy\DropshipPayout\Model;

use Unirgy\DropshipPayout\Helper\Data as DropshipPayoutHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipPayoutHelperData
     */
    protected $_payoutHlp;

    public function __construct(
        HelperData $helperData, 
        DropshipPayoutHelperData $dropshipPayoutHelperData,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_payoutHlp = $dropshipPayoutHelperData;

        parent::__construct($data);
    }

    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $ptHlp = $this->_payoutHlp;

        switch ($this->getPath()) {

        case 'payout_type':
            $options = [
                '' => __('* No Payout'),
                Payout::TYPE_AUTO      => __('Auto'),
                Payout::TYPE_MANUAL    => __('Manual'),
                Payout::TYPE_SCHEDULED => __('Scheduled'),
            ];
            break;
        case 'payout_type_internal':
            $options = [
                '' => __('* No Payout'),
                Payout::TYPE_AUTO      => __('Auto'),
                Payout::TYPE_MANUAL    => __('Manual'),
                Payout::TYPE_SCHEDULED => __('Scheduled'),
                Payout::TYPE_STATEMENT => __('Statement'),
            ];
            break;

        case 'payout_method':
            $options = [];
            foreach ($this->_hlp->config()->getPayoutMethod() as $methodName=>$method) {
                $options[$methodName] = __((string)@$method['title']);
            }
            break;
            
        case 'po_status_type':
            $options = [
                'statement' => __('Use Statement preferences'),
            	'payout' => __('Custom'),
            ];
            break;

        case 'payout_status':
        case 'po_payout_status':
            $options = [
                Payout::STATUS_PENDING    => __('Pending'),
                Payout::STATUS_SCHEDULED  => __('Scheduled'),
                Payout::STATUS_PROCESSING => __('Processing'),
                Payout::STATUS_HOLD       => __('Hold'),
                Payout::STATUS_PAYPAL_IPN => __('Waiting for Paypal IPN'),
                Payout::STATUS_PAID       => __('Paid'),
                Payout::STATUS_ERROR      => __('Error'),
                Payout::STATUS_CANCELED   => __('Canceled'),
            ];
            break;

        case 'payout_schedule_type':
            $options = $ptHlp->getPayoutSchedules('code2title');
            $options['-1'] = __('* Use Custom');
            break;

        default:
            throw new \Exception(__('Invalid request for source options: '.$this->getPath()));
        }

        if ($selector) {
            $options = [''=>__('* Please select')] + $options;
        }

        return $options;
    }
}
