<?php

namespace AmirrezaNasiri\LaravelToman\Tests;

use AmirrezaNasiri\LaravelToman\Facades\Payment;
use AmirrezaNasiri\LaravelToman\PaymentManager;

final class PaymentTest extends TestCase
{
    /** @test */
    public function resolves_to_gateway_manager()
    {
        $root = Payment::getFacadeRoot();
        self::assertInstanceOf(PaymentManager::class, $root);
    }
}
