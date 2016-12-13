<?php
namespace kushki\lib;

class VoidRequestBuilder extends RequestBuilder {

    private $ticket;
    private $amount;

    function __construct($merchantId, $ticket, $amount, $baseUrl = KushkiEnvironment::PRODUCTION) {
        parent::__construct($merchantId);
        $this->url = $baseUrl . KushkiConstant::VOID_URL;
        $this->amount = $amount;
        $this->ticket = $ticket;
    }

    public function createRequest() {
        $params = array(
            KushkiConstant::PARAMETER_TRANSACTION_TICKET => $this->ticket,
            KushkiConstant::PARAMETER_TRANSACTION_AMOUNT => $this->amount->toHash(),
            KushkiConstant::PARAMETER_CURRENCY_CODE => $this->currency,
            KushkiConstant::PARAMETER_MERCHANT_ID => $this->merchantId,
            KushkiConstant::PARAMETER_LANGUAGE => $this->language
        );

        $request = new KushkiRequest($this->url, $params, KushkiConstant::CONTENT_TYPE);
        return $request;
    }
}
