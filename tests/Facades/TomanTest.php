<?php

namespace Evryn\LaravelToman\Tests\Facades;

use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest as ZarinpalPendingRequest;
use Evryn\LaravelToman\Tests\TestCase;

final class TomanTest extends TestCase
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

        $gateway = Toman::data('key', 'value');

        self::assertInstanceOf(ZarinpalPendingRequest::class, $gateway);
        self::assertEquals([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ], $gateway->config());
    }
}
