<?php

namespace Evryn\LaravelToman\Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use InteractsWithExceptionHandling;

    protected function getPackageProviders($app)
    {
        return ['Evryn\LaravelToman\LaravelTomanServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Payment' => 'Evryn\LaravelToman\Facades\PaymentRequest'
        ];
    }
}
