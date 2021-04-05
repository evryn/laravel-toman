<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\AssertionFailedError;

final class FakeVerificationTest extends TestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var Factory */
    protected $factory;

    /** @var CallbackRequest */
    protected $callbackRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway();
        $this->factory = new Factory($this->gateway);
        $this->callbackRequest = new CallbackRequest($this->factory);
    }

    /** @test */
    public function can_fake_successful_verification()
    {
        $this->factory->fakeVerification()
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->successful();

        $this->gateway->setConfig([
            'merchant_id' => 'xxxxx-yyyyy-zzzzz'
        ]);

        $verification = $this->factory
            ->amount(5000)
            ->transactionId('tid_100')
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals(5000, $request->amount());
            self::assertEquals('tid_100', $request->transactionId());
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        self::assertTrue($verification->successful());
        self::assertFalse($verification->alreadyVerified());
        self::assertFalse($verification->failed());

        $verification->throw();
        self::assertEquals('tid_100', $verification->transactionId());
        self::assertEquals('ref_100', $verification->referenceId());
    }

    /** @test */
    public function can_fake_already_verified_verification()
    {
        $this->factory->fakeVerification()
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->alreadyVerified();

        $this->gateway->setConfig([
            'merchant_id' => 'xxxxx-yyyyy-zzzzz'
        ]);

        $verification = $this->factory
            ->amount(5000)
            ->transactionId('tid_100')
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals(5000, $request->amount());
            self::assertEquals('tid_100', $request->transactionId());
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        self::assertFalse($verification->successful());
        self::assertTrue($verification->alreadyVerified());
        self::assertFalse($verification->failed());

        $verification->throw();
        self::assertEquals('tid_100', $verification->transactionId());
        self::assertEquals('ref_100', $verification->referenceId());
    }

    /** @test */
    public function can_fake_failed_verification()
    {
        $this->factory->fakeVerification()
            ->withTransactionId('tid_100')
            ->failed('Payment has failed.', Status::FAILED_TRANSACTION);

        $this->gateway->setConfig([
            'merchant_id' => 'xxxxx-yyyyy-zzzzz'
        ]);

        $verification = $this->factory
            ->amount(5000)
            ->transactionId('tid_100')
            ->callback('http://example.com/callback')
            ->data('CustomData', 'Example')
            ->verify();

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            self::assertEquals(5000, $request->amount());
            self::assertEquals('tid_100', $request->transactionId());
            self::assertEquals('xxxxx-yyyyy-zzzzz', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        self::assertFalse($verification->successful());
        self::assertFalse($verification->alreadyVerified());
        self::assertTrue($verification->failed());

        self::assertEquals('tid_100', $verification->transactionId());

        try {
            $verification->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(Status::FAILED_TRANSACTION, $e->getCode());
            self::assertEquals('Payment has failed.', $e->getMessage());
            self::assertEquals('Payment has failed.', $verification->message());
            self::assertEquals(['Payment has failed.'], $verification->messages());
        }

        try {
            $verification->referenceId();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {}
    }

    /** @test */
    public function callback_sets_proper_values_from_faked_verification()
    {
        $this->factory->fakeVerification()
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->successful();

        $verification = $this->callbackRequest->validateResolved();

        self::assertEquals('tid_100', $verification->transactionId());
    }

    /** @test */
    public function assertion_fails_when_truth_check_is_false()
    {
        $this->factory->fakeVerification()
            ->withTransactionId('tid_100')
            ->withReferenceId('ref_100')
            ->successful();

        $this->factory->verify();

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertCheckedForVerification(function (PendingRequest $request) {
            return false;
        });
    }
}
