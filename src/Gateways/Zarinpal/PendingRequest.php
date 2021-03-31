<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\FakeRequest;
use Evryn\LaravelToman\FakeVerification;
use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Arr;

/**
 * Class Requester.
 *
 * @method PendingRequest amount(int $amount = null) Get or set amount of payment
 * @method PendingRequest callback(string $callbackUrl = null) Get or set absolute URL for payment verification callback
 * @method PendingRequest mobile(string $mobile = null) Get or set mobile data
 * @method PendingRequest merchantId(string $merchantId = null) Get or set gateway merchant ID
 * @method PendingRequest email(string $email = null) Get or set email data
 * @method PendingRequest description(string $description = null) Get or set description. `:amount` will be replaced by the given amount.
 * @method PendingRequest transactionId(string $transactionId = null) Get or set transaction ID. Can be used for specific transaction verification.
 */
class PendingRequest
{
    /** @var array Driver config */
    protected $config;

    /** @var array Payment gateway data holder */
    protected $data = [];

    protected $dataMethodMap = [
        'merchantid' => 'MerchantID',
        'amount' => 'Amount',
        'transactionid' => 'Authority',
        'callback' => 'CallbackURL',
        'mobile' => 'Mobile',
        'email' => 'Email',
        'description' => 'Description',
    ];

    /**
     * @var null|FakeRequest
     */
    private $fakeRequest = null;

    /**
     * @var null|FakeVerification
     */
    private $fakeVerification = null;

    /**
     * Requester constructor.
     * @param $config
     */
    public function __construct(Factory $factory, array $config = [])
    {
        $this->factory = $factory;
        $this->config($config);
    }

    public function config($key = null)
    {
        if (is_array($key)) {
            $this->config = $key;
            return $this;
        }

        return $key ? Arr::get($this->config, $key) : $this->config;
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
            return $key ? Arr::get($this->data, $key) : $this->data;
        }

        // Replace whole data
        if (func_num_args() === 1 && is_array($key)) {
            $this->data = $key;

            return $this;
        }

        // Set specific data
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Request a new payment from gateway.
     * @return RequestedPayment
     */
    public function request(): RequestedPaymentInterface
    {
        if ($this->fakeRequest) {
            return tap((new RequestFactory($this))->fakeFrom($this->fakeRequest), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return (new RequestFactory($this))->request();
    }

    /**
     * Check a transaction for verification
     * @return CheckedPaymentInterface
     */
    public function verify(): CheckedPaymentInterface
    {
        if ($this->fakeVerification) {
            return tap((new VerificationFactory($this))->fakeFrom($this->fakeVerification), function () {
                $this->factory->recordPendingRequest($this);
            });
        }

        return (new VerificationFactory($this))->verify();
    }

    public function stub(FakeRequest $fakeRequest = null, FakeVerification $fakeVerification = null)
    {
        $this->fakeRequest = $fakeRequest;
        $this->fakeVerification = $fakeVerification;
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
        if ($field = $this->dataMethodMap[strtolower($method)] ?? null) {
            if (isset($parameters[0])) {
                return $this->data($field, $parameters[0]);
            }

            return $this->data($field);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
