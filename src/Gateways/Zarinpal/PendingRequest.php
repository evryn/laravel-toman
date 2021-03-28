<?php

namespace Evryn\LaravelToman\Gateways\Zarinpal;

use Evryn\LaravelToman\Interfaces\CheckedPaymentInterface;
use Evryn\LaravelToman\Interfaces\RequestedPaymentInterface;
use Illuminate\Support\Arr;

/**
 * Class Requester.
 *
 * @method $this amount(int $amount)
 * @method $this callback(string $callbackUrl)
 * @method $this mobile(string $mobile)
 * @method $this merchantId(string $merchantId)
 * @method $this email(string $email)
 * @method $this description(string $description)
 * @method $this transactionId(string $transactionId)
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
     * Requester constructor.
     * @param $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    protected function setConfig(array $config = [])
    {
        $this->config = $config;

        return $this;
    }

    public function config(string $key = null)
    {
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
        return (new RequestFactory($this))->request();
    }

    /**
     * Check a transaction for verification
     * @return CheckedPaymentInterface
     */
    public function verify(): CheckedPaymentInterface
    {
        return (new VerificationFactory($this))->verify();
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
            return $this->data($field, $parameters[0]);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
