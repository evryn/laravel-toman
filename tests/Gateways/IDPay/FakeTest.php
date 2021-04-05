<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\IDPay\Gateway;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\AssertionFailedError;

final class FakeTest extends TestCase
{
    /** @var Factory */
    protected $factory;

    /** @var CallbackRequest */
    protected $callbackRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory(
            new Gateway()
        );

        $this->callbackRequest = new CallbackRequest($this->factory);
    }

    /** @test */
    public function assert_request_passes_when_truth_check_is_true()
    {
        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            return true;
        });
    }

    /** @test */
    public function assert_request_fails_when_truth_check_is_false()
    {
        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->request();

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertRequested(function (PendingRequest $request) {
            return false;
        });
    }

    /** @test */
    public function can_assert_sent_request_data()
    {
        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });
    }

    /** @test */
    public function can_assert_default_request_data_too()
    {
        Route::get('payment/callback', function () {})->name('payment.callback');
        config([
            'toman.callback_route' => 'payment.callback',
            'toman.description' => 'Pay :amount',
        ]);

        $this->factory = new Factory(new Gateway([
            'api_key' => 'xxxxx-yyyyy-zzzzz'
        ]));

        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->amount(500)->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals(route('payment.callback'), $request->callback());
            self::assertEquals('Pay 500', $request->description());

            return true;
        });
    }

    /** @test */
    public function fake_request_with_transaction_id_produces_successful_result()
    {
        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $request = $this->factory->request();

        self::assertTrue($request->successful());
        self::assertEquals('tid_100', $request->transactionId());
        self::assertNotEmpty($request->paymentUrl());
        self::assertInstanceOf(RedirectResponse::class, $request->pay());
        $request->throw();
    }

    /** @test */
    public function fake_request_without_transaction_id_produces_failed_result()
    {
        $this->factory->fakeRequest()
            ->failed('Your request has failed.', 500);

        $request = $this->factory->request();

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
        $this->factory->fakeVerification()->withTransactionId('tid_100')->withReferenceId('ref_100')
            ->successful();

        $this->factory->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return true;
        });
    }

    /** @test */
    public function assert_verification_fails_when_truth_check_is_false()
    {
        $this->factory->fakeVerification()->withTransactionId('tid_100')->withReferenceId('ref_100')
            ->successful();

        $this->factory->verify();

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return false;
        });
    }

    /** @test */
    public function can_assert_sent_verification_data()
    {
        $this->factory->fakeVerification()->withTransactionId('tid_100')->withReferenceId('ref_100')
            ->successful();

        $this->factory
            ->amount(500)
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals(500, $request->amount());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });
    }

    /** @test */
    public function can_assert_default_verification_data_too()
    {
        $this->factory = new Factory(new Gateway([
            'api_key' => 'xxxxx-yyyyy-zzzzz'
        ]));

        $this->factory->fakeVerification()->withTransactionId('tid_100')
            ->successful();

        $this->factory->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());

            return true;
        });
    }

    /** @test */
    public function can_verify_fake_successful_verification_manually()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->successful();

        $verification = $this->factory->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return $request->data() === [];
        });

        self::assertTrue($verification->successful());
        self::assertFalse($verification->alreadyVerified());
        self::assertFalse($verification->failed());
        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());
        self::assertEquals('ref_100', $verification->referenceId());
        $verification->throw();
    }

    /** @test */
    public function can_verify_fake_already_verified_verification_manually()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->alreadyVerified();

        $verification = $this->factory->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return $request->data() === [];
        });

        self::assertTrue($verification->alreadyVerified());
        self::assertFalse($verification->successful());
        self::assertFalse($verification->failed());
        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());
        self::assertEquals('ref_100', $verification->referenceId());
        $verification->throw();
    }

    /** @test */
    public function can_verify_fake_failed_verification_manually()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->failed('Your request has failed.', 500);

        $verification = $this->factory->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return $request->data() === [];
        });

        self::assertTrue($verification->failed());
        self::assertFalse($verification->successful());
        self::assertFalse($verification->alreadyVerified());
        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());

        try {
            $verification->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(500, $e->getCode());
            self::assertEquals('Your request has failed.', $e->getMessage());
            self::assertEquals('Your request has failed.', $verification->message());
            self::assertEquals(['Your request has failed.'], $verification->messages());
        }

        try {
            $verification->referenceId();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}
    }


    /** @test */
    public function can_verify_fake_successful_verification_from_callback_request()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->successful();

        $verification = $this->callbackRequest->validateResolved();

        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());
    }

    /** @test */
    public function can_verify_fake_already_verified_verification_from_callback_request()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->alreadyVerified();


        $verification = $this->callbackRequest->validateResolved();

        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());
    }

    /** @test */
    public function can_verify_fake_failed_verification_from_callback_request()
    {
        $this->factory->fakeVerification()
            ->withOrderId('order_1')
            ->withTransactionId('tid_100')
            ->failed();

        $verification = $this->callbackRequest->validateResolved();

        self::assertEquals('order_1', $verification->orderId());
        self::assertEquals('tid_100', $verification->transactionId());
    }

    /** @test */
    public function does_fail_validation_with_wrong_transaction_id()
    {
        $this->factory->fakeVerification()->withTransactionId('')->failed();

        $this->expectException(ValidationException::class);
        $this->callbackRequest->validateResolved();
    }
}
