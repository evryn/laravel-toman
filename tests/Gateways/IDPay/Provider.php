<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\Gateways\IDPay\Status;
use Evryn\LaravelToman\Money;

class Provider
{
    public static function endpointProvider()
    {
        return [
            'Sandbox' => [true],
            'Production' => [false],
        ];
    }

    public static function clientErrorProvider()
    {
        return [
            [403, Status::USER_IS_BLOCKED],
            [403, Status::API_KEY_NOT_FOUND],
            [403, Status::UNMATCHED_IP],
            [403, Status::UNVERIFIED_WEB_SERVICE],
            [403, Status::UNVERIFIED_BANK_ACCOUNT],
            [404, Status::WEB_SERVICE_NOT_FOUND],
            [401, Status::INVALID_WEB_SERVICE],
            [403, Status::INACTIVE_BANK_ACCOUNT],
            [406, Status::TRANSACTION_ID_REQUIRED],
            [406, Status::ORDER_ID_REQUIRED],
            [406, Status::AMOUNT_REQUIRED],
            [406, Status::AMOUNT_MIN],
            [406, Status::AMOUNT_MAX],
            [406, Status::AMOUNT_NOT_ALLOWED],
            [406, Status::CALLBACK_REQUIRED],
            [406, Status::UNMATCHED_CALLBACK_DOMAIN],
            [406, Status::INVALID_TRANSACTION_STATUS],
            [406, Status::INVALID_PAYMENT_CREATION_DATE],
            [406, Status::INVALID_PAYMENT_RECEIPT_DATE],
            [405, Status::TRANSACTION_NOT_CREATED],
            [400, Status::NO_RESULT_FOR_VERIFICATION],
            [405, Status::UNABLE_TO_VERIFY],
            [405, Status::PAYMENT_VERIFICATION_DATE_EXPIRED],
        ];
    }

    public static function rialBasedAmountProvider()
    {
        return [
            // [
            //     config value,
            //     input amount
            //     expected amount value in Rial
            // ],
            'Default currency is Toman (via config)' => [
                'toman',
                5,
                50,
            ],
            'Default currency is Rial (via config)' => [
                'rial',
                50,
                50,
            ],
            'Default currency is Toman (when no config is set)' => [
                null,
                50,
                500,
            ],
            'Currency is Rial (overridden)' => [
                'toman',
                Money::Rial(50),
                50,
            ],
            'Currency is Toman (overridden)' => [
                'toman',
                Money::Toman(5),
                50,
            ],
        ];
    }

    public static function fakeRialBasedAmountProvider()
    {
        return [
            'Default currency is Toman (via config)' => [
                'toman',
                5,
                Money::Rial(50),
            ],
            'Default currency is Rial (via config)' => [
                'rial',
                50,
                Money::Rial(50),
            ],
            'Default currency is Toman (when no config is set)' => [
                null,
                50,
                Money::Rial(500),
            ],
            'Currency is Rial (overridden) compared with Toman' => [
                'toman',
                Money::Rial(50),
                Money::Toman(5),
            ],
            'Currency is Toman (overridden) compared with Toman' => [
                'toman',
                Money::Toman(5),
                Money::Toman(5),
            ],
            'Currency is Toman (overridden) compared with Rial' => [
                'toman',
                Money::Toman(5),
                Money::Rial(50),
            ],
            'Currency is Rial (overridden) compared with Rial' => [
                'toman',
                Money::Rial(50),
                Money::Rial(50),
            ],
        ];
    }
}
