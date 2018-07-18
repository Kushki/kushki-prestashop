<?php

namespace kushki\lib;

class Kushki {
    private $merchantId;
    private $language;
    private $currency;
    private $environment;
    private $requestHandler;

    /**
     * @param string $merchantId
     * @param string $language
     * @param string $currency
     * @param string $environment
     */
    public function __construct($merchantId,
                                $language = KushkiLanguage::ES,
                                $currency = KushkiCurrency::USD,
                                $environment = KushkiEnvironment::PRODUCTION) {
        $this->merchantId = $merchantId;
        $this->language = $language;
        $this->currency = $currency;
        $this->environment = $environment;
        $this->requestHandler = new RequestHandler();
    }

    /**
     * @param string $token
     * @param Amount $amount
     * @return Transaction
     * @throws KushkiException
     */

    public function charge($token, $amount, $metadata = false) {
        $chargeRequestBuilder = new KushkiChargeRequest($this->merchantId, $token, $amount,$months = 0, $metadata,
            $this->environment, $this->currency);
        $request = $chargeRequestBuilder->charge();
        return $request;
    }

    /**
     * @param $token
     * @param $amount
     * @param $months
     * @return Transaction
     * @throws KushkiException
     */

    public function deferredCharge($token, $amount, $months, $metadata = false) {
        $chargeRequestBuilder = new KushkiChargeRequest($this->merchantId, $token, $amount, $months, $metadata,
            $this->environment, $this->currency);
        $request = $chargeRequestBuilder->charge();
        return $request;
    }

    /**
     * @param $ticketNumber
     * @return Transaction
     * @throws KushkiException
     */
    public function voidCharge($ticketNumber, $amount = false){
        $voidRequestBuilder = new KushkiVoidRequest($this->merchantId, $ticketNumber, $amount, $this->environment);
        $request = $voidRequestBuilder->voidCharge();
        return $request;
    }

    /**
     * @param $token
     * @param $planName
     * @param $periodicity
     * @param $contactDetails
     * @param $amount
     * @param $startDate
     * @return Transaction
     * @throws KushkiException
     */
    public function createSubscription($token, $planName, $periodicity, $contactDetails, $amount, $startDate,
                                       $metadata = false){
        $subscriptionRequest = new KushkiSubscriptionRequest($this->merchantId, $token, $planName, $periodicity,
            $contactDetails, $amount, $startDate, $metadata , $this->environment,
            $this->currency);
        $subscription = $subscriptionRequest->createSubscription();
        return $subscription;
    }

    /**
     * @param $subscriptionId
     * @param $body
     * @return Transaction
     * @throws KushkiException
     */
    public function updateSubscription($subscriptionId, $body){
        $subscriptionRequest = new KushkiSubscriptionUpdateRequest($this->merchantId, $subscriptionId, $body,
                                                                   $this->environment);
        $updateSubscription = $subscriptionRequest->updateSubscription();
        return $updateSubscription;
    }

    /**
     * @param $subscriptionId
     * @return Transaction
     * @throws KushkiException
     */
    public function chargeSubscription($subscriptionId, $metadata = false){
        $subscriptionRequest = new KushkiSubscriptionChargeRequest($this->merchantId, $subscriptionId, $metadata,
            $this->environment);
        $chargeSubscription = $subscriptionRequest->chargeSubscription();
        return $chargeSubscription;
    }

    public function getMerchantId() {
        return $this->merchantId;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getCurrency() {
        return $this->currency;
    }
    public function getEnvironment() {
        return $this->environment;
    }
}

?>
