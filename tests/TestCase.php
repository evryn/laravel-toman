<?php

namespace Evryn\LaravelToman\Tests;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert as PHPUnit;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
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

    protected function setUp(): void
    {
        parent::setUp();

        self::setupHttpMacros();
    }

    public static function setupHttpMacros()
    {
        Http::macro('assertNthRequestFieldEquals', function ($expected, $field, $nth) {
            PHPUnit::assertNotEmpty($this->recorded[$nth-1], "{$nth}th request has not been sent.");

            $request = $this->recorded[$nth-1][0];

            PHPUnit::assertEquals(
                $expected,
                $request->data()[$field],
                "{$nth}th request's field [{$field}] doesn't equal to [$expected]."
            );
        });
    }
}
