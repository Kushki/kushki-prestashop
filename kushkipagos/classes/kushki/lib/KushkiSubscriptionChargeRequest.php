<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/21/17
 * Time: 2:01 PM
 */

namespace kushki\lib;


class KushkiSubscriptionChargeRequest
{
    private $url;
    private $merchantId;
    private $subscriptionId;
    private $metadata;

    function __construct($merchantId, $subscriptionId, $metadata = false, $baseUrl = KushkiEnvironment::PRODUCTION)
    {
        $this->url = $baseUrl;
        $this->merchantId = $merchantId;
        $this->subscriptionId = $subscriptionId;
        $this->metadata = $metadata;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getData(){
        $body = array("language" => "es");
        if($this->metadata!=false)
            $body["metadata"] = $this->metadata;

        $data = array(
            "private-merchant-id" => $this->merchantId,
            "subscriptionId" => $this->subscriptionId,
            "body" =>$body);
        return $data;
    }

    public function chargeSubscription(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callChargeSubscription($this->url, $data);
    }
}