<?php

namespace Unirgy\Dropship\Model\Vendor\Decision;

interface DecisionInterface
{
    public function apply($items);
}