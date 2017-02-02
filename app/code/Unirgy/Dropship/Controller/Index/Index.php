<?php

namespace Unirgy\Dropship\Controller\Index;



class Index extends AbstractIndex
{
    public function execute()
    {
        $this->_forward('index', 'vendor');
    }
}
