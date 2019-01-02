<?php

namespace kushki\lib;

class Transaction {

    protected $contentType = "";
    protected $body;
    protected $code;

    public function __construct($contentType, $body, $response_code) {
        $this->contentType = $contentType;
        $this->body = $body;
        $this->code = $response_code;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function getBody() {
        return $this->body;
    }

    public function getCode() {
        return $this->code;
    }

    public function isSuccessful() {
        return $this->code === 200 || $this->code === 201 || $this->code === 204;
    }

    public function getToken() {
        return $this->body->transaction_token;
    }

    public function getTicketNumber() {
        if(isset($this->body->ticket_number))
            return $this->body->ticket_number;
        else
            return $this->body->ticketNumber;
    }

    public function getApprovedAmount() {
        return $this->body->approved_amount;
    }

    public function getResponseCode() {
        if(isset($this->body->response_code))
            return $this->body->response_code;
        else
            return $this->body->code;
    }

    public function getResponseText() {
        if(isset($this->body->response_text))
            return $this->body->response_text;
        else
            return $this->body->message;
    }

    public function getSubscriptionId(){
        return $this->body->subscriptionId;
    }
}

?>
