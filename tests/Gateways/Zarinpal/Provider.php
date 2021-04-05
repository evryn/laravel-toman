<?php


namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;


use Evryn\LaravelToman\Gateways\Zarinpal\Status;

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
}
