<?php

namespace Unirgy\DropshipMicrosite\Model;

class RequestHttp extends \Magento\Framework\App\Request\Http
{
    public function umicrositeReset()
    {
        $this->originalPathInfo = '';
        $this->requestString = '';
        $this->pathInfo = '';
        return $this;
    }
}