<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Illuminate\Support\Arr;
use ReflectionClass;

class Status
{
    // Documented status codes
    const INCOMPLETE_DATA = -1;
    const WRONG_IP_OR_MERCHANT_ID = -2;
    const SHAPARAK_LIMITED = -3;
    const INSUFFICIENT_USER_LEVEL = -4;
    const REQUEST_NOT_FOUND = -11;
    const UNABLE_TO_EDIT_REQUEST = -12;
    const NO_FINANCIAL_OPERATION = -21;
    const FAILED_TRANSACTION = -22;
    const AMOUNTS_NOT_EQUAL = -33;
    const TRANSACTION_SPLITTING_LIMITED = -34;
    const METHOD_ACCESS_DENIED = -40;
    const INVALID_ADDITIONAL_DATA = -41;
    const INVALID_EXPIRATION_RANGE = -42;
    const REQUEST_ARCHIVED = -54;
    const OPERATION_SUCCEED = 100;
    const ALREADY_VERIFIED = 101;

    // Package related
    const NOT_PAID = 9000;
    const UNEXPECTED = 9001;

    public static function toMessage($status)
    {
        $translationKey = strtolower(self::getConstantName($status)) ?: $status;

        return __("toman::zarinpal.status.$translationKey");
    }

    protected static function getConstantName($value)
    {
        $constants = array_flip((new ReflectionClass(static::class))->getConstants());

        return Arr::get($constants, $value);
    }
}
