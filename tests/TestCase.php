<?php

namespace Evryn\LaravelToman\Tests;

use Evryn\LaravelToman\LaravelTomanServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelTomanServiceProvider::class];
    }
}
