<?php

namespace AmirrezaNasiri\LaravelToman\Tests;

use AmirrezaNasiri\LaravelToman\Facades\PaymentRequest;
use AmirrezaNasiri\LaravelToman\PaymentRequestGatewayManager;

final class PaymentRequestTest extends TestCase
{
    /** @test */
    public function resolves_to_gateway_manager()
    {
        $root = PaymentRequest::getFacadeRoot();
        self::assertInstanceOf(PaymentRequestGatewayManager::class, $root);
    }
}
