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

use Unirgy\Dropship\Model\Vendor\Statement\StatementInterface;

class Offline implements MethodInterface
{
    protected $_hasExtraInfo=false;
    public function hasExtraInfo($payout)
    {
        return $this->_hasExtraInfo;
    }
    protected $_isOnline=false;
    public function isOnline()
    {
        return $this->_isOnline;
    }
    public function pay($payout)
    {
        if ($payout instanceof StatementInterface) {
            $payout = [$payout];
        }
        foreach ($payout as $pt) {
            $pt->afterPay();
        }
        return true;
    }
}