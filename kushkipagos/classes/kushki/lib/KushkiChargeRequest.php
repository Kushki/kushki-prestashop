<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/15/17
 * Time: 10:50 AM
 */

namespace kushki\lib;


class KushkiChargeRequest{
    private $url;
    private $token;
    private $amount;
    private $merchantId;
    private $metadata;
    private $currency;
    private $language = KushkiLanguage::ES;
    private $months;

    function __construct($merchantId, $token, $amount, $months, $metadata = false,
                         $baseUrl = KushkiEnvironment::PRODUCTION, $currency = KushkiCurrency::USD) {
        $this->url = $baseUrl;
        $this->token = $token;
        $this->amount = $amount;
        $this->months = $months;
        $this->merchantId = $merchantId;
        $this->metadata = $metadata;
        $this->currency = $currency;
    }

    public function getUrl() {
        return $this->url;
    }
    public function getParameter($parameterName) {
        $params = $this->getData()["body"];
        if (isset($params[$parameterName])) {
            return $params[$parameterName];
        }
        throw new KushkiException(KushkiConstant::PARAMETER_DO_NOT_EXIST);
    }
    public function getData(){
        $arrAmount = $this->amount->toHash();
        $arrAmount["currency"] = $this->currency;
        $body = array(
            "token" => $this->token,
            "amount" => $arrAmount,
            "language" => $this->language
        );
        $metadataArray = array("metadata" => $this->metadata);
        if($this->months > 0)
            $body["months"] = $this->months;
        if($this->metadata!=false){
            $body = array_merge($body, $metadataArray);
        }

        $data = array(
            "private-merchant-id" => $this->merchantId,
            "body" =>$body
        );
        return $data;
    }

    public function charge(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callCharge($this->url, $data);
    }

}