<?php

namespace AmirrezaNasiri\LaravelToman\Tests\Facades;

use AmirrezaNasiri\LaravelToman\Facades\PaymentRequest;
use AmirrezaNasiri\LaravelToman\Managers\PaymentRequestManager;
use AmirrezaNasiri\LaravelToman\Tests\TestCase;

final class PaymentRequestTest extends TestCase
{
    /** @test */
    public function resolves_to_gateway_manager()
    {
        $root = PaymentRequest::getFacadeRoot();
        self::assertInstanceOf(PaymentRequestManager::class, $root);
    }
}
