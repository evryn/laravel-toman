<?php

namespace Evryn\LaravelToman\Facades;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Facades\Facade;

/**
 * Class Toman
 * @package Evryn\LaravelToman\Facades
 *
 * @method static PendingRequest amount(int $amount = null) Get or set amount of payment
 * @method static PendingRequest callback(string $callbackUrl = null) Get or set absolute URL for payment verification callback
 * @method static PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method static PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method static PendingRequest email(string $email = null) Get or set email data
 * @method static PendingRequest description(string $description = null) Get or set description. `:amount` will be replaced by the given amount.
 * @method static PendingRequest transactionId(string $transactionId = null) Get or set transaction ID. Can be used for specific transaction verification.
 *
 * @method static RequestedPaymentInterface request() Request a new payment
 *
 * @method static PendingRequest fakeRequest(string $transactionId) Stub a successful payment request with the given transaction ID
 * @method static PendingRequest fakeFailedRequest() Stub a failed payment request
 * @method static void assertRequested(callable $callback)
 */
class Toman extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}