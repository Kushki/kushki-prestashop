<?php

namespace kushki\lib;

class Validations {
    static function validateAmount($amount) {
        return self::validateNumber($amount, 0, 12, "El monto");
    }

    static function validateNumber($number, $minValue, $maxLength, $amountName) {
        if (is_null($number)) {
            throw new KushkiException($amountName . " no puede ser un valor nulo");
        }
        if ($number < $minValue) {
            throw new KushkiException($amountName . " debe ser superior o igual a " . $minValue);
        }
        $validNumber = number_format($number, 2, ".", "");
        if (strlen($validNumber) > $maxLength) {
            throw new KushkiException($amountName . " debe tener " . $maxLength . " o menos dígitos");
        }
        return $validNumber;
    }
}
