<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method PendingRequest amount(int $amount = null) Get or set amount of payment
 * @method PendingRequest callback(string $callbackUrl = null) Get or set absolute URL for payment verification callback
 * @method PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method PendingRequest email(string $email = null) Get or set email data
 * @method PendingRequest description(string $description = null) Get or set description. `:amount` will be replaced by the given amount.
 * @method PendingRequest transactionId(string $transactionId = null) Get or set transaction ID. Can be used for specific transaction verification.
 *
 * @method static RequestedPaymentInterface request() Request a new payment
 * @method static CheckedPaymentInterface verify() Verify a payment
 */
class Factory
{
    /**
     * @var GatewayInterface
     */
    private $gateway;

    /**
     * @var bool
     */
    private $recording = false;

    /**
     * @var null|FakeRequest
     */
    private $fakeRequest = null;

    /**
     * @var null|FakeVerification
     */
    private $fakeVerification = null;

    /**
     * @var null|PendingRequest
     */
    private $recordedPendingRequest = null;

    use Macroable {
        __call as macroCall;
    }

    public function __construct(GatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function fakeRequest()
    {
        $this->record();

        return $this->fakeRequest = new FakeRequest();
    }

    public function fakeVerification()
    {
        $this->record();

        return $this->fakeVerification = new FakeVerification();
    }

    /**
     * Assert that a payment request is recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertRequested($callback)
    {
        if (!$this->recordedPendingRequest || !$this->fakeRequest) {
            PHPUnit::fail('No payment request is recorded.');
        }

        PHPUnit::assertTrue(
            $this->isRecorded($callback),
            'Recorded payment request does not match the expectation.'
        );
    }

    /**
     * Assert that a payment verification is recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertCheckedForVerification($callback)
    {
        if (!$this->recordedPendingRequest || !$this->fakeVerification) {
            PHPUnit::fail('No payment verification is recorded.');
        }

        PHPUnit::assertTrue(
            $this->isRecorded($callback),
            'Recorded payment verification does not match the expectation.'
        );
    }

    private function record()
    {
        $this->recording = true;
    }

    public function recordPendingRequest($pendingRequest)
    {
        if ($this->recording) {
            $this->recordedPendingRequest = $pendingRequest;
        }
    }

    /**
     * Determine if requested with given truth test
     *
     * @param  null|callable  $callback
     * @return bool
     */
    private function isRecorded($callback = null)
    {
        if (empty($this->recordedPendingRequest)) {
            return false;
        }

        $callback = $callback ?: function () {
            return true;
        };

        return $callback($this->recordedPendingRequest);
    }

    public function newPendingRequest(): PendingRequest
    {
        return new PendingRequest($this, $this->gateway);
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap($this->newPendingRequest(), function ($pendingRequest) {
            $pendingRequest->stub($this->fakeRequest, $this->fakeVerification);
        })->{$method}(...$parameters);
    }
}
