<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\IDPay\Gateway;
use Evryn\LaravelToman\Gateways\IDPay\Status;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\AssertionFailedError;

final class FakeRequestTest extends TestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var Factory */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway();
        $this->factory = new Factory($this->gateway);
    }

    /** @test */
    public function can_fake_successful_request()
    {
        config([
            'toman.description' => 'Pay :amount',
        ]);

        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->gateway->setConfig([
            'sandbox' => false,
            'api_key' => 'xxxx-yyyyy'
        ]);

        $request = $this->factory
            ->orderId('order_1')
            ->callback('http://example.com/callback')
            ->mobile('09350000000')
            ->amount(5000)
            ->data('CustomData', 'Example')
            ->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxx-yyyyy', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('order_1', $request->orderId());
            self::assertEquals('09350000000', $request->mobile());
            self::assertEquals(5000, $request->amount());
            self::assertEquals('Pay 5000', $request->description());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        $request->throw();
        self::assertTrue($request->successful());
        self::assertFalse($request->failed());
        self::assertEquals('tid_100', $request->transactionId());
        self::assertNotEmpty($request->paymentUrl());
        self::assertInstanceOf(RedirectResponse::class, $request->pay());
    }

    /** @test */
    public function can_fake_failed_request()
    {
        config([
            'toman.description' => 'Pay :amount',
        ]);

        $this->factory->fakeRequest()->failed('Your request has failed.', Status::API_KEY_NOT_FOUND);

        $this->gateway->setConfig([
            'sandbox' => false,
            'api_key' => 'xxxx-yyyyy'
        ]);

        $request = $this->factory
            ->orderId('order_1')
            ->callback('http://example.com/callback')
            ->mobile('09350000000')
            ->amount(5000)
            ->data('CustomData', 'Example')
            ->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxx-yyyyy', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('order_1', $request->orderId());
            self::assertEquals('09350000000', $request->mobile());
            self::assertEquals(5000, $request->amount());
            self::assertEquals('Pay 5000', $request->description());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        self::assertFalse($request->successful());
        self::assertTrue($request->failed());

        try {
            $request->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(Status::API_KEY_NOT_FOUND, $e->getCode());
            self::assertEquals('Your request has failed.', $e->getMessage());
            self::assertEquals('Your request has failed.', $request->message());
            self::assertEquals(['Your request has failed.'], $request->messages());
        }

        try {
            $request->transactionId();
            self::fail();
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
    public function assertion_fails_when_truth_check_is_false()
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
}
