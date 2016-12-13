<?php
namespace kushki\lib;

class ChargeRequestBuilder extends RequestBuilder {

    private $token;
    private $amount;

    function __construct($merchantId, $token, $amount, $baseUrl = KushkiEnvironment::PRODUCTION) {
        parent::__construct($merchantId);
        $this->url = $baseUrl . KushkiConstant::CHARGE_URL;
        $this->token = $token;
        $this->amount = $amount;
    }

    public function createRequest() {
        $params = array(
            KushkiConstant::PARAMETER_TRANSACTION_TOKEN => $this->token,
            KushkiConstant::PARAMETER_TRANSACTION_AMOUNT => $this->amount->toHash(),
            KushkiConstant::PARAMETER_CURRENCY_CODE => $this->currency,
            KushkiConstant::PARAMETER_MERCHANT_ID => $this->merchantId,
            KushkiConstant::PARAMETER_LANGUAGE => $this->language
        );

        $request = new KushkiRequest($this->url, $params, KushkiConstant::CONTENT_TYPE);
        return $request;
    }
}
