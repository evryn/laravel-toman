<?php

namespace AmirrezaNasiri\LaravelToman\Tests;

use AmirrezaNasiri\LaravelToman\Exceptions\Exception;
use AmirrezaNasiri\LaravelToman\Exceptions\GatewayException;
use AmirrezaNasiri\LaravelToman\Exceptions\InvalidConfigException;

final class ExceptionsTest extends TestCase
{
    /**
     * It's not a functional test however, very useful in case of
     * catching package-related exceptions.
     *
     * @test
     */
    public function exceptions_are_namespaced_to_package()
    {
        self::assertInstanceOf(Exception::class, new InvalidConfigException('x'));
        self::assertInstanceOf(Exception::class, new GatewayException);
    }
}
