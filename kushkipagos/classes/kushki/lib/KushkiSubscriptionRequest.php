<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/20/17
 * Time: 12:35 PM
 */

namespace kushki\lib;


class KushkiSubscriptionRequest
{
    private $url;
    private $token;
    private $amount;
    private $merchantId;
    private $metadata;
    private $currency;
    private $planName;
    private $periodicity;
    private $contactDetails;
    private $startDate;
    private $language = KushkiLanguage::ES;

    function __construct($merchantId, $token, $planName, $periodicity, $contactDetails, $amount, $startDate,
                         $metadata = false, $baseUrl = KushkiEnvironment::PRODUCTION,
                         $currency = KushkiCurrency::USD) {
        $this->url = $baseUrl;
        $this->token = $token;
        $this->amount = $amount;
        $this->merchantId = $merchantId;
        $this->metadata = $metadata;
        $this->currency = $currency;
        $this->planName = $planName;
        $this->periodicity = $periodicity;
        $this->contactDetails = $contactDetails;
        $this->startDate = $startDate;
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
            "planName" => $this->planName,
            "periodicity" => $this->periodicity,
            "contactDetails" => $this->contactDetails,
            "amount" => $arrAmount,
            "startDate" => $this->startDate,
            "language" => $this->language
        );
        $metadataArray = array("metadata" => $this->metadata);
        if($this->metadata!=false)
            $body = array_merge($body, $metadataArray);
        $data = array(
            "private-merchant-id" => $this->merchantId,
            "body" =>$body
        );
        return $data;
    }

    public function createSubscription(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callCreateSubscription($this->url, $data);
    }
}