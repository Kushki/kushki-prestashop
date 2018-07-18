<?php

namespace kushki\lib;

class KushkiConstant {
    const VERSION = '1.0';
    const TOKENS_URL = "/tokens";
    const CHARGE_API_URL = "/charges";
    const SUBSCRIPTION_API_URL = "/subscriptions";
    const VOID_URL = "/void";
    const DEFERRED_URL = "/deferred";
    const PARAMETER_MERCHANT_ID = "merchant_identifier";
    const PARAMETER_LANGUAGE = "language_indicator";
    const PARAMETER_TRANSACTION_TOKEN = "transaction_token";
    const PARAMETER_TRANSACTION_TICKET = "ticket_number";
    const PARAMETER_TRANSACTION_ID = "transaction_id";
    const PARAMETER_CURRENCY_CODE = "currency_code";
    const PARAMETER_TRANSACTION_AMOUNT = "transaction_amount";
    const PARAMETER_TICKET_NUMBER = "ticket_number";
    const PARAMETER_DO_NOT_EXIST = "Parameter does not exist";
    const PARAMETER_DEFERRED = "deferred";
    const PARAMETER_MONTHS = "months";
    const PARAMETER_INTEREST = "rate_of_interest";
    const PARAMETER_ERRORS = "error";
    const PARAMETER_ERRORS_MESSAGE = "message";
    const PARAMETER_ERRORS_CODE = "code";

    const CONTENT_TYPE = "application/json";

    const KUSHKI_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC81t5iu5C0JxYq5/XNPiD5ol3Z
w8rw3LtFIUm7y3m8o8wv5qVnzGh6XwQ8LWypdkbBDKWZZrAUd3lybZOP7/82Nb1/
noYj8ixVRdbnYtbsSAbu9PxjB7a/7LCGKsugLkou74PJDadQweM88kzQOx/kzAyV
bS9gCCVUguHcq2vRRQIDAQAB
-----END PUBLIC KEY-----";
}

?>
