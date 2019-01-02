<?php

namespace kushki\lib;

class Amount {

    private $subtotalIVA;
    private $subtotalIVA0;
    private $iva;
    private $ice;
    private $extraTaxes;

    public function __construct($subtotalIVA, $iva, $subtotalIVA0, $auxTax) {
        $this->subtotalIVA = $subtotalIVA;
        $this->subtotalIVA0 = $subtotalIVA0;
        $this->iva = $iva;
        $this->ice = 0;
        $this->extraTaxes = new ExtraTaxes(0, 0, 0, 0);
        if(is_numeric($auxTax)) {
            $this->ice = $auxTax;
        } else if($auxTax instanceof ExtraTaxes) {
            $this->extraTaxes = $auxTax;
        } else {
            $this->extraTaxes = null;
        }
    }

    public function toHash() {
        $validatedSubtotalIVA = (float) Validations::validateNumber($this->subtotalIVA, 0, 12, "El subtotal IVA");
        $validatedSubtotalIVA0 = (float) Validations::validateNumber($this->subtotalIVA0, 0, 12, "El subtotal IVA 0");
        $validatedIva = (float) Validations::validateNumber($this->iva, 0, 12, "El IVA");
        $validatedIce = (float) Validations::validateNumber($this->ice, 0, 12, "El ICE");
        $total = $this->subtotalIVA + $this->subtotalIVA0 + $this->iva + $this->ice + $this->extraTaxes->getTotalExtraTaxes();
        $validatedTotal = Validations::validateNumber($total, 0, 12, "El total");
        $arrayHash = array("subtotalIva" => $validatedSubtotalIVA,
                           "subtotalIva0" => $validatedSubtotalIVA0,
                           "iva" => $validatedIva);
        if($validatedIce > 0) {
            $arrayHash["ice"] = $validatedIce;
        }
        $arrayHash["Total_amount"] = $validatedTotal;
        if(count($this->extraTaxes->toHashArray()) > 0) {
            $arrayHash["extraTaxes"] = $this->extraTaxes->toHashArray();
        }
        return $arrayHash;
    }


}
