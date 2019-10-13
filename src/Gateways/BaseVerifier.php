<?php

namespace Evryn\LaravelToman\Gateways;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Evryn\LaravelToman\Contracts\PaymentVerifier;

abstract class BaseVerifier implements PaymentVerifier
{
    /** @var array Driver config */
    protected $config;

    /** @var Client HTTP client used for requests */
    protected $client;

    /** @var array Payment gateway data holder */
    protected $data = [];

    protected function setConfig($config)
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
}
