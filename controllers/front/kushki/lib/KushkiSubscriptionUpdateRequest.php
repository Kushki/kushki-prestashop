<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/21/17
 * Time: 9:09 AM
 */

namespace kushki\lib;


class KushkiSubscriptionUpdateRequest
{
    private $url;
    private $merchantId;
    private $subscriptionId;
    private $body;

    function __construct($merchantId, $subscriptionId, $body ,$baseUrl = KushkiEnvironment::PRODUCTION)
    {
        $this->url = $baseUrl;
        $this->merchantId = $merchantId;
        $this->subscriptionId = $subscriptionId;
        $this->body = $body;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getParameter($parameterName) {
        $params = $this->getData();
        if (isset($params[$parameterName])) {
            return $params[$parameterName];
        }
        throw new KushkiException(KushkiConstant::PARAMETER_DO_NOT_EXIST);
    }

    public function getData(){
        if (isset($this->body["amount"])) {
            $this->body["amount"] = $this->body["amount"]->toHash();
            if(isset($this->body["currency"])){
                $this->body["amount"]["currency"] = $this->body["currency"];
                unset($this->body["currency"]);
            }
        }
        $data = array(
            "private-merchant-id" => $this->merchantId,
            "subscriptionId" => $this->subscriptionId,
            "body" =>$this->body);
        return $data;
    }

    public function updateSubscription(){
        $data = $this->getData();
        $response = new KushkiClient();
        return $response = $response->callUpdateSubscription($this->url, $data);
    }

}