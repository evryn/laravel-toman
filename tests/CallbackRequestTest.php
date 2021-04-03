<?php

namespace Evryn\LaravelToman\Tests;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway as ZarinpalGateway;
use Evryn\LaravelToman\PendingRequest;
use Illuminate\Validation\ValidationException;

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
            ]
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

    /** @test */
    public function validates_zarinpal_callback_when_resolved()
    {
        config([
            'toman.default' => 'zarinpal',
            'toman.gateways.zarinpal' => [
                'sandbox' => true,
                'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
            ]
        ]);

        Toman::fakeVerification()->successful()->withTransactionId('');

        $this->expectException(ValidationException::class);

        app(CallbackRequest::class);
    }
}
