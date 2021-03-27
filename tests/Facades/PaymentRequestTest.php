<?php

namespace Evryn\LaravelToman\Tests\Facades;

use Evryn\LaravelToman\Facades\PaymentRequest;
use Evryn\LaravelToman\Gateways\Zarinpal\Requester as ZarinpalRequester;
use Evryn\LaravelToman\Managers\PaymentRequestManager;
use Evryn\LaravelToman\Tests\TestCase;

final class PaymentRequestTest extends TestCase
{
    /** @test */
    public function resolves_to_configured_zarinpal_gateway()
    {
        config([
            'toman.default' => 'zarinpal',
            'toman.gateways.zarinpal' => [
                'sandbox' => true,
                'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
            ]
        ]);

        $gateway = PaymentRequest::getFacadeRoot()->driver();

        self::assertInstanceOf(ZarinpalRequester::class, $gateway);
        self::assertEquals([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ], $gateway->getConfig());
    }
}
