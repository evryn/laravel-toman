<?php

namespace Evryn\LaravelToman\Tests\Facades;

use Evryn\LaravelToman\Facades\PaymentRequest;
use Evryn\LaravelToman\Managers\PaymentRequestManager;
use Evryn\LaravelToman\Tests\TestCase;

final class PaymentRequestTest extends TestCase
{
    /** @test */
    public function resolves_to_gateway_manager()
    {
        $root = PaymentRequest::getFacadeRoot();
        self::assertInstanceOf(PaymentRequestManager::class, $root);
    }
}
