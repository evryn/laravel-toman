<?php

namespace Evryn\LaravelToman\Facades;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Evryn\LaravelToman\PendingRequest;
use Illuminate\Support\Facades\Facade;

/**
 * Class Toman.
 *
 * @method static PendingRequest amount(int $amount = null) Get or set amount of payment
 * @method static PendingRequest callback(string $callbackUrl = null) Get or set absolute URL for payment verification callback
 * @method static PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method static PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method static PendingRequest email(string $email = null) Get or set email data
 * @method static PendingRequest description(string $description = null) Get or set description. `:amount` will be replaced by the given amount.
 * @method static PendingRequest transactionId(string $transactionId = null) Get or set transaction ID. Can be used for specific transaction verification.
 * @method static PendingRequest name(string $name = null) Get or set payer name.
 * @method static PendingRequest orderId(string $id = null) Get or set order ID.
 * @method static PendingRequest inspectCallbackRequest() Validate callback request and fill default values based on the request or the stubbed fake one.
 * @method static RequestedPaymentInterface request() Request a new payment
 * @method static CheckedPaymentInterface verify() Verify a payment
 * @method static PendingRequest gateway(string $name = null, array $config = null) Get a new gateway pending request
 * @method static FakeRequest fakeRequest() Stub a fake payment request
 * @method static void assertRequested(callable $callback)
 * @method static FakeVerification fakeVerification() Stub a fake payment verification
 * @method static void assertCheckedForVerification(callable $callback)
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
