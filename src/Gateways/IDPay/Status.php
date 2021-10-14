<?php

namespace Evryn\LaravelToman\Gateways\IDPay;

use Illuminate\Support\Arr;
use ReflectionClass;

class Status
{
    // Documented status codes
    const PAYMENT_IS_NOT_DONE = 1;
    const UNSUCCESSFUL_PAYMENT = 2;
    const UNEXPECTED_ERROR = 3;
    const BLOCKED = 4;
    const RETURNED_TO_THE_PAYER = 5;
    const RETURNED_BY_SYSTEM = 6;
    const CANCELLED = 7;
    const REDIRECTED_TO_THE_GATEWAY = 8;
    const PENDING_FOR_VERIFICATION = 10;
    const SUCCESSFUL = 100;
    const ALREADY_VERIFIED = 101;
    const DEPOSITED_TO_THE_RECIPIENT = 200;

    // Documented error codes
    const USER_IS_BLOCKED = 11;
    const API_KEY_NOT_FOUND = 12;
    const UNMATCHED_IP = 13;
    const UNVERIFIED_WEB_SERVICE = 14;
    const UNVERIFIED_BANK_ACCOUNT = 21;
    const WEB_SERVICE_NOT_FOUND = 22;
    const INVALID_WEB_SERVICE = 23;
    const INACTIVE_BANK_ACCOUNT = 24;
    const TRANSACTION_ID_REQUIRED = 31;
    const ORDER_ID_REQUIRED = 32;
    const AMOUNT_REQUIRED = 33;
    const AMOUNT_MIN = 34;
    const AMOUNT_MAX = 35;
    const AMOUNT_NOT_ALLOWED = 36;
    const CALLBACK_REQUIRED = 37;
    const UNMATCHED_CALLBACK_DOMAIN = 38;
    const INVALID_TRANSACTION_STATUS = 41;
    const INVALID_PAYMENT_CREATION_DATE = 42;
    const INVALID_PAYMENT_RECEIPT_DATE = 43;
    const TRANSACTION_NOT_CREATED = 51;
    const NO_RESULT_FOR_VERIFICATION = 52;
    const UNABLE_TO_VERIFY = 53;
    const PAYMENT_VERIFICATION_DATE_EXPIRED = 54;

    // Package related
    const UNEXPECTED = 9001;

    public static function toMessage($status)
    {
        $translationKey = strtolower(self::getConstantName($status));

        return __("toman::idpay.status.$translationKey");
    }

    protected static function getConstantName($value)
    {
        $constants = array_flip((new ReflectionClass(static::class))->getConstants());

        return Arr::get($constants, $value);
    }
}
