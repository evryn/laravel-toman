<?php

namespace Evryn\LaravelToman;

use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @method PendingRequest amount(int $amount = null) Get or set amount of payment
 * @method PendingRequest callback(string $callbackUrl = null) Get or set absolute URL for payment verification callback
 * @method PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method PendingRequest email(string $email = null) Get or set email data
 * @method PendingRequest description(string $description = null) Get or set description. `:amount` will be replaced by the given amount.
 * @method PendingRequest transactionId(string $transactionId = null) Get or set transaction ID. Can be used for specific transaction verification.
 * @method PendingRequest name(string $name = null) Get or set payer name.
 * @method PendingRequest orderId(string $id = null) Get or set order ID.
 */
class PendingRequest
{
    /** @var array Payment gateway data holder */
    protected $data = [];

    /** @var array */
    protected $customData = [];

    /**
     * @var null|FakeRequest
     */
    private $fakeRequest = null;

    /**
     * @var null|FakeVerification
     */
    private $fakeVerification = null;
    /**
     * @var GatewayInterface
     */
    private $gateway;

    protected $alwaysCompute = ['description'];

    /**
     * Requester constructor.
     * @param $config
     */
    public function __construct(Factory $factory, GatewayInterface $gateway)
    {
        $this->factory = $factory;
        $this->gateway = $gateway;
    }

    /**
     * Get or set data
     *
     * @param string|null $key
     * @param null $value
     * @return $this|array
     */
    public function data(string $key = null, $value = null)
    {
        // Get all data
        if (func_num_args() === 0) {
            return $this->data;
        }

        // Get specific data
        if (func_num_args() === 1 && is_string($key)) {
            return Arr::get($this->customData, $key) ?? $this->getData($key);
        }

        // Replace whole data
        if (func_num_args() === 1 && is_array($key)) {
            $this->data = $key;

            return $this;
        }

        // Set specific data
        $this->customData[$key] = $value;

        return $this;
    }

    public function provideForGateway(array $fields): Collection
    {
        return collect($fields)->mapWithKeys(function ($field) {
            return [$this->getFieldNameForGateway($field) =>  $this->getData($field)];
        })->merge($this->customData);
    }

    /**
     * Request a new payment from gateway.
     * @return RequestedPaymentInterface
     */
    public function request(): RequestedPaymentInterface
    {
        if ($this->fakeRequest) {
            return tap($this->gateway->requestPayment($this, $this->fakeRequest), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return $this->gateway->requestPayment($this);
    }

    /**
     * Check a transaction for verification
     * @return CheckedPaymentInterface
     */
    public function verify(): CheckedPaymentInterface
    {
        if ($this->fakeVerification) {
            return tap(($this->gateway->verifyPayment($this, $this->fakeVerification)), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return $this->gateway->verifyPayment($this);
    }

    public function stub(FakeRequest $fakeRequest = null, FakeVerification $fakeVerification = null)
    {
        $this->fakeRequest = $fakeRequest;
        $this->fakeVerification = $fakeVerification;
    }

    public function inspectCallbackRequest()
    {
        $this->gateway->inspectCallbackRequest($this, $this->fakeVerification);

        return $this;
    }

    /**
     * Get underlying gateway
     *
     * @return GatewayInterface
     */
    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Dynamically call the setters
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if ($this->canAccessDataAlias($method)) {
            if (isset($parameters[0])) {
                return $this->setData($method, $parameters[0]);
            }

            return $this->getData($method);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    protected function canAccessDataAlias(string $alias): bool
    {
        // First, we check to see if there is a computed one
        if (method_exists($this, $this->getComputerMethodFor($alias))) {
            return true;
        }

        // If it's supported by the gateway, we can use it too
        if ($this->getFieldNameForGateway($alias)) {
            return true;
        }

        return false;
    }

    protected function getFieldNameForGateway(string $field)
    {
        foreach ($this->gateway->getAliasDataFields() as $key => $value) {
            if (strtolower($key) === strtolower($field)) {
                return $value;
            }
        }

        return null;
    }

    protected function getCallbackData()
    {
        if ($callback = $this->getRawData('callback')) {
            return $callback;
        }

        if (config('toman.callback_route')) {
            return route(config('toman.callback_route'));
        }

        return null;
    }

    protected function getDescriptionData()
    {
        return str_replace(
            ':amount',
            $this->amount(),
            $this->getRawData('description') ?: config('toman.description')
        );
    }

    protected function setData(string $field, $value)
    {
        $this->data[strtolower($field)] = $value;

        return $this;
    }

    protected function getData(string $alias)
    {
        if (in_array(strtolower($alias), $this->alwaysCompute)) {
            return $this->getComputed($alias);
        }

        if ($value = $this->getRawData($alias)) {
            return $value;
        }

        if (method_exists($this->gateway, $method = $this->getComputerMethodFor($alias))) {
            return $this->gateway->{$method}();
        }

        return $this->getComputed($alias) ?? null;
    }

    protected function getRawData(string $alias)
    {
        return Arr::get($this->data, strtolower($alias));
    }

    protected function getComputerMethodFor(string $alias)
    {
        return "get{$alias}Data";
    }

    protected function getComputed(string $alias)
    {
        return method_exists($this, $method = $this->getComputerMethodFor($alias)) ? $this->{$method}() : null;
    }
}
