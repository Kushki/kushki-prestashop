<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 3/14/17
 * Time: 4:38 PM
 */

namespace kushki\lib;

use HttpException;
use HttpRequest;

class KushkiClient
{

    function callAPI($method, $url, $data)
    {
        if($method == "POST"){
            $responseRaw = \Httpful\Request::post($url)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"]
                ))
                ->body(json_encode($data["body"]))
                ->send();
            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
        elseif ($method == "PATCH"){
            $responseRaw = \Httpful\Request::patch($url)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"]
                ))
                ->body(json_encode($data["body"]))
                ->send();
            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
        else
        {
            if(isset($data["body"])){
                $responseRaw = \Httpful\Request::delete($url)
                    ->sendsJson()
                    ->withStrictSSL()
                    ->addHeaders(array(
                        'private-merchant-id' => $data["private-merchant-id"]))
                    ->body(json_encode($data["body"]))
                    ->send();
            }
            else {
                $responseRaw = \Httpful\Request::delete($url)
                    ->sendsJson()
                    ->withStrictSSL()
                    ->addHeaders(array(
                        'private-merchant-id' => $data["private-merchant-id"]))
                    ->send();
            }

            return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
        }
    }

    function callCharge($url, $data)
    {
        $url = $url . KushkiConstant::CHARGE_API_URL;
        $method = "POST";
        $requestCharge = $this->callAPI($method,$url,$data);
        return $requestCharge;
    }

    function callCreateSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL;
        $method = "POST";
        $requestCreateSubscription = $this->callAPI($method,$url,$data);
        return $requestCreateSubscription;
    }

    function callUpdateSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL . "/" . $data["subscriptionId"];
        $method = 'PATCH';
        $requestUpdateSubscription = $this->callAPI($method,$url,$data);
        return $requestUpdateSubscription;
    }

    function callChargeSubscription($url, $data)
    {
        $url = $url . KushkiConstant::SUBSCRIPTION_API_URL . "/" . $data["subscriptionId"] .
            KushkiConstant::CHARGE_API_URL;
        $method = 'POST';
        $requestChargeSubscription = $this->callAPI($method,$url,$data);
        return $requestChargeSubscription;
    }

    function callVoidCharge($url, $data)
    {
        $url = $url . KushkiConstant::CHARGE_API_URL . "/" . $data["ticketNumber"];
        $method = 'DELETE';
        $requestVoidCharge = $this->callAPI($method,$url,$data);
        return $requestVoidCharge;
    }
}

?>