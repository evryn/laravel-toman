<?php

namespace Evryn\LaravelToman\Tests\Misc;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\IDPay\Gateway as IDPayGateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway as ZarinpalGateway;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;

final class CallbackRequestTest extends TestCase
{
    /** @test */
    public function resolves_zarinpal_callback()
    {
        config([
            'toman.default' => 'zarinpal',
            'toman.gateways.zarinpal' => [
                'sandbox' => true,
                'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
            ],
        ]);

        Toman::fakeVerification()->successful()->withTransactionId('A123');

        $pendingRequest = app(CallbackRequest::class)->data('key', 'value');

        self::assertInstanceOf(PendingRequest::class, $pendingRequest);
        self::assertInstanceOf(ZarinpalGateway::class, $pendingRequest->getGateway());
        self::assertEquals([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ], $pendingRequest->getGateway()->getConfig());
    }

}
