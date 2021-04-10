<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin PendingRequest
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
        if (! $this->recordedPendingRequest || ! $this->fakeRequest) {
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
        if (! $this->recordedPendingRequest || ! $this->fakeVerification) {
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
     * Determine if requested with given truth test.
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
