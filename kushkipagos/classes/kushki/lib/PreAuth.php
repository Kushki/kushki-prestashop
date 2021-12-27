<?php

namespace kushki\lib;

class PreAuth {

    protected $details;
    protected $ticketNumber;
    protected $transactionReference;

    public function __construct($details, $ticketNumber, $transactionReference) {
        $this->details = $details;
        $this->ticketNumber = $ticketNumber;
        $this->transactionReference = $transactionReference;
    }

    public function getDetails() {
        return $this->details;
    }

    public function getTransactionReference() {
        return $this->transactionReference;
    }

    public function isApproval() {
        return $this->details->transactionStatus == "APPROVAL" && $this->details->responseCode == "000";
    }

    public function getTicketNumber() {
        return $this->ticketNumber;
    }

}

?>
