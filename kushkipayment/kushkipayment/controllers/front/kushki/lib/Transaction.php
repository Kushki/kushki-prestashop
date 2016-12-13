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
        return $this->code === 200;
    }

    public function getToken() {
        return $this->body->transaction_token;
    }

    public function getTicketNumber() {
        return $this->body->ticket_number;
    }

    public function getApprovedAmount() {
        return $this->body->approved_amount;
    }

    public function getResponseCode() {
        return $this->body->response_code;
    }

    public function getResponseText() {
        return $this->body->response_text;
    }
}

?>
