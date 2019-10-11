<?php

namespace AmirrezaNasiri\LaravelToman\Gateways;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use AmirrezaNasiri\LaravelToman\Contracts\PaymentGateway;

abstract class BaseGateway implements PaymentGateway
{
    /** @var array Driver config */
    protected $config;

    /** @var Client HTTP client used for requests */
    protected $client;

    /** @var mixed Transaction ID returned in payment request */
    protected $transactionId;

    /** @var array Payment gateway data holder */
    protected $data = [];

    public function __construct($config)
    {
        $this->setConfig($config);
        $this->client = app('laravel-toman.guzzle-client');
    }

    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig($key = null)
    {
        return $key ? Arr::get($this->config, $key) : $this->config;
    }

    public function data($key, $value = null)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function getData($key = null)
    {
        return $key ? Arr::get($this->data, $key) : $this->data;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
