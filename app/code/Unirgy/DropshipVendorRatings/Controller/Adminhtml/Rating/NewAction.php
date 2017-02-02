<?php

namespace Unirgy\DropshipVendorRatings\Controller\Adminhtml\Rating;



class NewAction extends AbstractRating
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
