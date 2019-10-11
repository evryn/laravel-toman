<?php

namespace AmirrezaNasiri\LaravelToman\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['AmirrezaNasiri\LaravelToman\LaravelTomanServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Payment' => 'AmirrezaNasiri\LaravelToman\Facades\Payment'
        ];
    }
}
