<?php

namespace Evryn\LaravelToman;

use BadMethodCallException;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\GatewayInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @method PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method PendingRequest email(string $email = null) Get or set email data
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
     *
     * @param  $config
     */
    public function __construct(Factory $factory, GatewayInterface $gateway)
    {
        $this->factory = $factory;
        $this->gateway = $gateway;
    }

    /**
     * Get or set data.
     *
     * @param  string|null  $key
     * @param  null  $value
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
            $value = $this->getData($field);

            if (self::normalizeField($field) === 'amount') {
                $value = $this->provideAmount();
            }

            return [$this->getFieldNameForGateway($field) =>  $value];
        })->merge($this->customData);
    }

    /**
     * Request a new payment from gateway.
     *
     * @return RequestedPaymentInterface
     */
    public function request(): RequestedPaymentInterface
    {
        if ($this->fakeRequest) {
            return tap($this->getGateway()->requestPayment($this, $this->fakeRequest), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return $this->getGateway()->requestPayment($this);
    }

    /**
     * Check a transaction for verification.
     *
     * @return CheckedPaymentInterface
     */
    public function verify(): CheckedPaymentInterface
    {
        if ($this->fakeVerification) {
            return tap($this->getGateway()->verifyPayment($this, $this->fakeVerification), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return $this->getGateway()->verifyPayment($this);
    }

    public function stub(FakeRequest $fakeRequest = null, FakeVerification $fakeVerification = null)
    {
        $this->fakeRequest = $fakeRequest;
        $this->fakeVerification = $fakeVerification;
    }

    public function inspectCallbackRequest()
    {
        $this->getGateway()->inspectCallbackRequest($this, $this->fakeVerification);

        return $this;
    }

    /**
     * Get underlying gateway.
     *
     * @return GatewayInterface
     */
    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Dynamically call the setters.
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

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    protected function canAccessDataAlias(string $alias): bool
    {
        // If it's supported by the gateway, we can use it too
        if ($this->getFieldNameForGateway($alias)) {
            return true;
        }

        return false;
    }

    protected function getFieldNameForGateway(string $field)
    {
        foreach ($this->getGateway()->getAliasDataFields() as $key => $value) {
            if (self::normalizeField($key) === self::normalizeField($field)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get or set absolute URL for payment verification callback.
     *
     * @param  string|null  $callback
     * @return $this|string|null
     */
    public function callback(string $callback = null)
    {
        if (! is_null($callback)) {
            $this->setData('callback', $callback);

            return $this;
        }

        if ($callback = $this->getRawData('callback')) {
            return $callback;
        }

        if ($callback = config('toman.callback_route')) {
            return route($callback);
        }

        return null;
    }

    /**
     * Get or set amount of the payment.
     *
     * @param  null|int|Money  $amount
     * @return PendingRequest|Money
     */
    public function amount($amount = null)
    {
        if (is_null($amount)) {
            return $this->getRawData('amount');
        }

        if (! $amount instanceof Money) {
            $amount = new Money(
                $amount,
                config('toman.currency') ?: 'toman'
            );
        }

        $this->setData('amount', $amount);

        return $this;
    }

    protected function provideAmount()
    {
        if (! $this->amount()) {
            return null;
        }

        return $this->amount()->value($this->getGateway()->getCurrency());
    }

    /**
     * Get or set description. `:amount` will be replaced by the given amount.
     *
     * @param  string|null  $description
     * @return $this|string|null
     */
    public function description(string $description = null)
    {
        if (! is_null($description)) {
            $this->setData('description', $description);

            return $this;
        }

        return str_replace(
            ':amount',
            $this->provideAmount(),
            $this->getRawData('description') ?? config('toman.description')
        );
    }

    protected function setData(string $field, $value)
    {
        $this->data[self::normalizeField($field)] = $value;

        return $this;
    }

    protected function getData(string $alias)
    {
        if (method_exists($this, $alias)) {
            return $this->{$alias}();
        }

        if ($data = $this->getRawData($alias)) {
            return $data;
        }

        if (method_exists($this->gateway, $method = 'get'.$alias.'Data')) {
            return $this->getGateway()->{$method}();
        }

        return null;
    }

    public function getRawData(string $alias)
    {
        return Arr::get($this->data, self::normalizeField($alias));
    }

    protected static function normalizeField(string $field): string
    {
        return strtolower($field);
    }
}
