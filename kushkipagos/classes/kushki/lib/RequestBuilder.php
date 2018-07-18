<?php

namespace kushki\lib;

use kushki\lib\KushkiRequest;
use kushki\lib\KushkiConstant;

abstract class RequestBuilder {
    protected $url;
    protected $currency = KushkiCurrency::USD;
    protected $merchantId;
    protected $language = KushkiLanguage::ES;

    function __construct($merchantId, $currency = KushkiCurrency::USD) {
        $this->merchantId = $merchantId;
        $this->currency = $currency;
    }

    abstract protected function createRequest();
}
