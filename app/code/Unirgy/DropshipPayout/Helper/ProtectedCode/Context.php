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
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

namespace Unirgy\DropshipPayout\Helper\ProtectedCode;

use Psr\Log\LoggerInterface;
use Unirgy\DropshipPayout\Helper\Data as HelperData;
use Unirgy\DropshipPayout\Model\Payout;
use Unirgy\Dropship\Helper\Data as DropshipHelperData;
use Unirgy\Dropship\Helper\ProtectedCode as HelperProtectedCode;

class Context
{
    /**
     * @var HelperData
     */
    public $_payoutHlp;

    /**
     * @var DropshipHelperData
     */
    public $_hlp;

    public function __construct(
        \Unirgy\DropshipPayout\Helper\Data $helperData,
        \Unirgy\Dropship\Helper\Data $dropshipHelperData
    )
    {
        $this->_payoutHlp = $helperData;
        $this->_hlp = $dropshipHelperData;
    }
}