<?php

namespace Unirgy\Dropship\Model\Vendor\Decision;



class LocalIfInStock
    extends AbstractDecision
{
    public function apply($items)
    {
        parent::apply($items);



        return $this;
    }
}