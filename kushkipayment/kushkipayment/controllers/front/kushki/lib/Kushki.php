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
    public function charge($token, $amount) {
        $chargeRequestBuilder = new ChargeRequestBuilder($this->merchantId, $token, $amount, $this->environment);
        $request = $chargeRequestBuilder->createRequest();
        return $this->requestHandler->call($request);
    }

    /**
     * @param $token
     * @param $amount
     * @param $months
     * @return Transaction
     * @throws KushkiException
     */
    public function deferredCharge($token, $amount, $months) {
        $deferredChargeRequestBuilder = new DeferredChargeRequestBuilder($this->merchantId, $token, $amount, $months,
                                                                         $this->environment);
        $request = $deferredChargeRequestBuilder->createRequest();
        return $this->requestHandler->call($request);
    }

    /**
     * @param $ticket
     * @param $amount
     * @return Transaction
     * @throws KushkiException
     */
    public function voidCharge($ticket, $amount) {
        $voidRequestBuilder = new VoidRequestBuilder($this->merchantId, $ticket, $amount, $this->environment);
        $request = $voidRequestBuilder->createRequest();
        return $this->requestHandler->call($request);
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
}

?>
