<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Money;

class Provider
{
    public static function endpointProvider()
    {
        return [
            'Sandbox' => [true, 'sandbox.zarinpal.com'],
            'Production' => [false, 'www.zarinpal.com'],
        ];
    }

    public function clientErrorProvider()
    {
        return [
            [200, Status::INCOMPLETE_DATA, 'incomplete_data'], // 404 HTTP code is not guaranteed for errors
            [200, -10, '-10'], // Yeah! It happens, and is not documented!
            [404, Status::INCOMPLETE_DATA, 'incomplete_data'],
            [404, Status::WRONG_IP_OR_MERCHANT_ID, 'wrong_ip_or_merchant_id'],
            [404, Status::SHAPARAK_LIMITED, 'shaparak_limited'],
            [404, Status::INSUFFICIENT_USER_LEVEL, 'insufficient_user_level'],
            [404, Status::REQUEST_NOT_FOUND, 'request_not_found'],
            [404, Status::UNABLE_TO_EDIT_REQUEST, 'unable_to_edit_request'],
            [404, Status::NO_FINANCIAL_OPERATION, 'no_financial_operation'],
            [404, Status::FAILED_TRANSACTION, 'failed_transaction'],
            [404, Status::AMOUNTS_NOT_EQUAL, 'amounts_not_equal'],
            [404, Status::TRANSACTION_SPLITTING_LIMITED, 'transaction_splitting_limited'],
            [404, Status::METHOD_ACCESS_DENIED, 'method_access_denied'],
            [404, Status::INVALID_ADDITIONAL_DATA, 'invalid_additional_data'],
            [404, Status::INVALID_EXPIRATION_RANGE, 'invalid_expiration_range'],
            [404, Status::REQUEST_ARCHIVED, 'request_archived'],
            [404, Status::UNEXPECTED, 'unexpected'],
        ];
    }

    public static function tomanBasedAmountProvider()
    {
        return [
            // [
            //     config value,
            //     input amount
            //     expected amount value in Toman
            // ],
            'Default currency is Toman (via config)' => [
                'toman',
                5,
                5,
            ],
            'Default currency is Rial (via config)' => [
                'rial',
                50,
                5,
            ],
            'Default currency is Toman (when no config is set)' => [
                null,
                5,
                5,
            ],
            'Currency is Rial (overridden)' => [
                'toman',
                Money::Rial(50),
                5,
            ],
            'Currency is Toman (overridden)' => [
                'toman',
                Money::Toman(50),
                50,
            ],
        ];
    }

    public static function fakeTomanBasedAmountProvider()
    {
        return [
            'Default currency is Toman (via config)' => [
                'toman',
                5,
                Money::Toman(5),
            ],
            'Default currency is Rial (via config)' => [
                'rial',
                50,
                Money::Toman(5),
            ],
            'Default currency is Toman (when no config is set)' => [
                null,
                5,
                Money::Toman(5),
            ],
            'Currency is Rial (overridden) compared with Toman' => [
                'toman',
                Money::Rial(50),
                Money::Toman(5),
            ],
            'Currency is Toman (overridden) compared with Toman' => [
                'toman',
                Money::Toman(50),
                Money::Toman(50),
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
