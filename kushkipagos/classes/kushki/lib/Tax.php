<?php

namespace kushki\lib;

class Tax
{
    private $taxId;
    private $taxName;
    private $taxAmount;

    /**
     * Tax constructor.
     * @param $taxId
     * @param $taxAmount
     * @param $taxName
     */
    public function __construct($taxId, $taxName, $taxAmount)
    {
        $this->taxId = $taxId;
        $this->taxAmount = $taxAmount;
        $this->taxName = $taxName;
    }

    public function toHashApi() {
        $validatedAmount = (float) Validations::validateNumber($this->taxAmount, 0, 12, "Amount");
        return array(
            $this->taxName => $validatedAmount
        );
    }

    public function getAmount() {
        return $this->taxAmount;
    }
}
