<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest;
use Evryn\LaravelToman\Managers\PendingRequestManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

class Factory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var bool
     */
    private $recording = false;

    /**
     * @var null|FakeRequest
     */
    private $fakeRequest = null;

    /**
     * @var null|PendingRequest
     */
    private $recordedPendingRequest = null;

    use Macroable {
        __call as macroCall;
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function fakeRequest()
    {
        $this->record();

        return $this->fakeRequest = new FakeRequest();
    }

    /**
     * Assert that a payment request is recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertRequested($callback)
    {
        if (!$this->recordedPendingRequest) {
            PHPUnit::fail('No payment request is recorded.');
        }

        PHPUnit::assertTrue(
            $this->isRecorded($callback),
            'Recorded payment request does not match the expectation.'
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
     * @param  callable  $callback
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

    public function gateway(string $name = null, array $config = [])
    {
        $pendingRequest = (new PendingRequestManager($this->container, $this))->driver($name);

        if ($config) {
            $pendingRequest->config($config);
        }

        $pendingRequest->stub($this->fakeRequest);

        return $pendingRequest;
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

        return tap($this->gateway(), function ($request) {
            // $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }
}
