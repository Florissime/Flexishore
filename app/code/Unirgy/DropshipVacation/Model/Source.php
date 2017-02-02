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
 * @package    Unirgy_DropshipSplit
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

/**
* Currently not in use
*/
namespace Unirgy\DropshipVacation\Model;

use Unirgy\DropshipVacation\Helper\Data as DropshipVacationHelperData;
use Unirgy\Dropship\Helper\Data as HelperData;
use Unirgy\Dropship\Model\Source\AbstractSource;

class Source extends AbstractSource
{
    /**
     * @var HelperData
     */
    protected $_hlp;

    /**
     * @var DropshipVacationHelperData
     */
    protected $_vacHlp;

    public function __construct(
        HelperData $helperData, 
        DropshipVacationHelperData $dropshipVacationHelperData,
        array $data = []
    )
    {
        $this->_hlp = $helperData;
        $this->_vacHlp = $dropshipVacationHelperData;

        parent::__construct($data);
    }

    const MODE_NOT_VACATION     = 0;
    const MODE_VACATION_NOTIFY  = 1;
    const MODE_VACATION_DISABLE = 2;
    public function toOptionHash($selector=false)
    {
        $hlp = $this->_hlp;
        $hlpv = $this->_vacHlp;

        switch ($this->getPath()) {

        case 'vacation_mode':
            $options = [
                0 => __('Not Vacation'),
                1 => __('Notify Customer On Availability'),
                2 => __('Disable Products'),
            ];
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