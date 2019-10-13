<?php

namespace Evryn\LaravelToman\Tests\Facades;

use Evryn\LaravelToman\Facades\PaymentRequest;
use Evryn\LaravelToman\Facades\PaymentVerification;
use Evryn\LaravelToman\Managers\PaymentRequestManager;
use Evryn\LaravelToman\Managers\PaymentVerificationManager;
use Evryn\LaravelToman\Tests\TestCase;

final class PaymentVerificationTest extends TestCase
{
    /** @test */
    public function resolves_to_gateway_manager()
    {
        $root = PaymentVerification::getFacadeRoot();
        self::assertInstanceOf(PaymentVerificationManager::class, $root);
    }
}
