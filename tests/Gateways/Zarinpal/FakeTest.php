<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\Zarinpal\CheckedPayment;
use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\AssertionFailedError;

final class FakeTest extends TestCase
{
    /** @test */
    public function assert_request_passes_when_truth_check_is_true()
    {
        Toman::fakeRequest('A000123');

        Toman::request();

        Toman::assertRequested(function (PendingRequest $request) {
            return true;
        });
    }

    /** @test */
    public function assert_request_fails_when_truth_check_is_false()
    {
        Toman::fakeRequest('A000123');

        Toman::request();

        $this->expectException(AssertionFailedError::class);

        Toman::assertRequested(function (PendingRequest $request) {
            return false;
        });
    }

    /** @test */
    public function can_assert_sent_request_data()
    {
        Toman::fakeRequest('A000123');

        Toman
            ::amount(500)
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->request();

        Toman::assertRequested(function (PendingRequest $request) {
            self::assertEquals(500, $request->amount());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });
    }

    /** @test */
    public function fake_request_with_transaction_id_produces_successful_result()
    {
        Toman::fakeRequest('A0001234');

        $request = Toman::request();

        self::assertTrue($request->successful());
        self::assertEquals('A0001234', $request->transactionId());
        self::assertNotEmpty($request->paymentUrl());
        self::assertInstanceOf(RedirectResponse::class, $request->pay());
        $request->throw();
    }

    /** @test */
    public function fake_request_without_transaction_id_produces_failed_result()
    {
        Toman::fakeFailedRequest();

        $request = Toman::request();

        self::assertTrue($request->failed());

        try {
            $request->transactionId();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}

        try {
            $request->pay();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}

        try {
            $request->paymentUrl();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}

        try {
            $request->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}
    }
}
