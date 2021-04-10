<?php

namespace Evryn\LaravelToman\Tests\Facades;

use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\IDPay\Gateway as IDPayGateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway as ZarinpalGateway;
use Evryn\LaravelToman\PendingRequest;
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
            ],
        ]);

        $pendingRequest = Toman::getFacadeRoot()->data('key', 'value');

        self::assertInstanceOf(PendingRequest::class, $pendingRequest);
        self::assertInstanceOf(ZarinpalGateway::class, $pendingRequest->getGateway());
        self::assertEquals([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ], $pendingRequest->getGateway()->getConfig());
    }

    /** @test */
    public function resolves_to_configured_idpay_gateway()
    {
        config([
            'toman.default' => 'idpay',
            'toman.gateways.idpay' => [
                'sandbox' => true,
                'api_key' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
            ],
        ]);

        $pendingRequest = Toman::getFacadeRoot()->data('key', 'value');

        self::assertInstanceOf(PendingRequest::class, $pendingRequest);
        self::assertInstanceOf(IDPayGateway::class, $pendingRequest->getGateway());
        self::assertEquals([
            'sandbox' => true,
            'api_key' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ], $pendingRequest->getGateway()->getConfig());
    }
}
