<?php

namespace AmirrezaNasiri\LaravelToman\Tests;

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
