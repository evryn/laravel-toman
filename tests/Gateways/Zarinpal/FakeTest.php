<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Facades\Toman;
use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\AssertionFailedError;

final class FakeTest extends TestCase
{
    /** @test */
    public function assert_request_passes_when_truth_check_is_true()
    {
        Toman::fakeRequest()->withTransactionId('A001234')->successful();

        Toman::request();

        Toman::assertRequested(function (PendingRequest $request) {
            return true;
        });
    }

    /** @test */
    public function assert_request_fails_when_truth_check_is_false()
    {
        Toman::fakeRequest()->withTransactionId('A001234')->successful();

        Toman::request();

        $this->expectException(AssertionFailedError::class);

        Toman::assertRequested(function (PendingRequest $request) {
            return false;
        });
    }

    /** @test */
    public function can_assert_sent_request_data()
    {
        Toman::fakeRequest()->withTransactionId('A001234')->successful();

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
    public function can_assert_default_request_data_too()
    {
        Toman::fakeRequest()->withTransactionId('A001234')->successful();
        Route::get('payment/callback', function () {})->name('payment.callback');
        config([
            'toman.callback_route' => 'payment.callback',
            'toman.description' => 'Pay :amount',
            'toman.gateways.zarinpal.merchant_id' => 'xxxxx-yyyyy-zzzzz',
        ]);

        Toman::amount(500)->request();

        Toman::assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals(route('payment.callback'), $request->callback());
            self::assertEquals('Pay 500', $request->description());

            return true;
        });
    }

    /** @test */
    public function fake_request_with_transaction_id_produces_successful_result()
    {
        Toman::fakeRequest()->withTransactionId('A001234')->successful();

        $request = Toman::request();

        self::assertTrue($request->successful());
        self::assertEquals('A001234', $request->transactionId());
        self::assertNotEmpty($request->paymentUrl());
        self::assertInstanceOf(RedirectResponse::class, $request->pay());
        $request->throw();
    }

    /** @test */
    public function fake_request_without_transaction_id_produces_failed_result()
    {
        Toman::fakeRequest()->failed('Your request has failed.', 500);

        $request = Toman::request();

        self::assertTrue($request->failed());

        try {
            $request->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(500, $e->getCode());
            self::assertEquals('Your request has failed.', $e->getMessage());
            self::assertEquals('Your request has failed.', $request->message());
            self::assertEquals(['Your request has failed.'], $request->messages());
        }

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
    }

    /** @test */
    public function assert_verification_passes_when_truth_check_is_true()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->withReferenceId('R123')->successful();

        Toman::verify();

        Toman::assertCheckedForVerification(function (PendingRequest $request) {
            return true;
        });
    }

    /** @test */
    public function assert_verification_fails_when_truth_check_is_false()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->withReferenceId('R123')->successful();

        Toman::verify();

        $this->expectException(AssertionFailedError::class);

        Toman::assertCheckedForVerification(function (PendingRequest $request) {
            return false;
        });
    }

    /** @test */
    public function can_assert_sent_verification_data()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->withReferenceId('R123')->successful();

        Toman
            ::amount(500)
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->verify();

        Toman::assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals(500, $request->amount());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });
    }

    /** @test */
    public function can_assert_default_verification_data_too()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->successful();
        config(['toman.gateways.zarinpal.merchant_id' => 'xxxxx-yyyyy-zzzzz']);
        request()->merge(['Authority' => 'A0123456']);

        Toman::amount(500)->verify();

        Toman::assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals('A0123456', $request->transactionId());

            return true;
        });
    }

    /** @test */
    public function fake_successful_verification_produces_proper_results()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->withReferenceId('R123')->successful();

        $verification = Toman::verify();

        self::assertTrue($verification->successful());
        self::assertFalse($verification->alreadyVerified());
        self::assertFalse($verification->failed());
        self::assertEquals('A001234', $verification->transactionId());
        self::assertEquals('R123', $verification->referenceId());
        $verification->throw();
    }

    /** @test */
    public function fake_already_verified_verification_produces_proper_results()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->withReferenceId('R123')->alreadyVerified();

        $verification = Toman::verify();

        self::assertTrue($verification->alreadyVerified());
        self::assertFalse($verification->successful());
        self::assertFalse($verification->failed());
        self::assertEquals('A001234', $verification->transactionId());
        self::assertEquals('R123', $verification->referenceId());
        $verification->throw();
    }

    /** @test */
    public function fake_failed_verification_produces_proper_results()
    {
        Toman::fakeVerification()->withTransactionId('A001234')->failed('Your request has failed.', 500);

        $request = Toman::verify();

        self::assertTrue($request->failed());
        self::assertFalse($request->successful());
        self::assertFalse($request->alreadyVerified());
        self::assertEquals('A001234', $request->transactionId());

        try {
            $request->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(500, $e->getCode());
            self::assertEquals('Your request has failed.', $e->getMessage());
            self::assertEquals('Your request has failed.', $request->message());
            self::assertEquals(['Your request has failed.'], $request->messages());
        }

        try {
            $request->referenceId();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}
    }
}
