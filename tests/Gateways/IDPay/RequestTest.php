<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\IDPay\Gateway;
use Evryn\LaravelToman\Gateways\IDPay\RequestedPayment;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

final class RequestTest extends TestCase
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

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::endpointProvider()
     */
    public function can_request_payment(bool $sandbox)
    {
        // By sending a request to create a new IDPay transaction,
        // we need to ensure that it sends request to proper endpoint, with correct
        // data and amount.
        // We also need to check that created payment can be redirected to default gateway
        // or the specific one.

        config([
            'toman.currency' => 'rial',
        ]);

        Http::fake([
            'https://api.idpay.ir/v1.1/payment' => Http::response([
                'id' => 'tid1000',
                'link' => 'https://idpay.ir/p/ws-sandbox/tid1000',
            ], 201),
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'api_key' => 'xxxx-xxxx-xxxx-xxxx',
        ]);

        $gateway = $this->factory
            ->callback('https://example.com/callback')
            ->orderId('order1000')
            ->amount(1500)
            ->description('Payment for :amount.')
            ->name('Amirreza')
            ->mobile('09350000000')
            ->email('amirreza@example.com');

        tap($gateway->request(), function (RequestedPayment $request) use ($sandbox) {
            Http::assertSent(function (Request $request) use ($sandbox) {
                return ($sandbox ? $request->header('X-SANDBOX')[0] === '1' : ! $request->hasHeader('X-SANDBOX'))
                    && $request->header('X-API-KEY')[0] === 'xxxx-xxxx-xxxx-xxxx'
                    && $request->method() === 'POST'
                    && $request->isJson()
                    && $request->url() === 'https://api.idpay.ir/v1.1/payment'
                    && $request['callback'] === 'https://example.com/callback'
                    && $request['order_id'] === 'order1000'
                    && $request['amount'] == 1500
                    && $request['name'] === 'Amirreza'
                    && $request['phone'] === '09350000000'
                    && $request['mail'] === 'amirreza@example.com'
                    && $request['desc'] === 'Payment for 1500.';
            });

            // Since request is successful, we need to ensure that nothing can be
            // thrown and determiners are correct
            $request->throw();
            self::assertTrue($request->successful());
            self::assertFalse($request->failed());

            self::assertEquals('tid1000', $request->transactionId());

            $redirectDefault = $request->pay();
            self::assertInstanceOf(RedirectResponse::class, $redirectDefault);
            self::assertEquals('https://idpay.ir/p/ws-sandbox/tid1000', $redirectDefault->getTargetUrl());
            self::assertEquals('https://idpay.ir/p/ws-sandbox/tid1000', $request->paymentUrl());
        });
    }

    /** @test */
    public function fails_with_server_error()
    {
        // When requesting a valid payment result in 5xx error, we consider this as an
        // issue in gateway-side (server) and expect an expect API to provide proper results

        Http::fake([
            'https://api.idpay.ir/v1.1/payment' => Http::response(null, 555),
        ]);

        tap($this->factory->request(), function (RequestedPayment $request) {
            self::assertFalse($request->successful());
            self::assertTrue($request->failed());

            try {
                $request->throw();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {
                self::assertEquals(555, $exception->getCode());
                self::assertEquals($exception->getMessage(), $request->message());
                self::assertEquals($exception->getCode(), $request->status());
            }

            try {
                $request->transactionId();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {
            }

            try {
                $request->paymentUrl();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {
            }

            try {
                $request->pay();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {
            }
        });
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::clientErrorProvider()
     */
    public function fails_with_client_error($httpStatus, $statusCode)
    {
        // When requesting a valid payment result in 4xx error, with given error messages,
        // we consider this as an issue in merchant-side (client) and expect an expect API
        // to provide proper results

        Http::fake([
            'https://api.idpay.ir/v1.1/payment' => Http::response([
                'error_code' => $statusCode,
                'error_message' => 'Something failed ...',
            ], $httpStatus),
        ]);

        tap($this->factory->request(), function (RequestedPayment $request) use ($statusCode) {
            self::assertFalse($request->successful());
            self::assertTrue($request->failed());

            try {
                $request->throw();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayClientException $exception) {
                self::assertEquals($statusCode, $exception->getCode());
                self::assertEquals($exception->getCode(), $request->status());

                self::assertEquals('Something failed ...', $exception->getMessage());
                self::assertEquals('Something failed ...', $request->message());
                self::assertEquals(['Something failed ...'], $request->messages());
            }

            try {
                $request->transactionId();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
            }

            try {
                $request->paymentUrl();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
            }

            try {
                $request->pay();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
            }
        });
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::rialBasedAmountProvider()
     */
    public function can_set_amount_in_different_currencies($configCurrency, $actualAmount, $expectedAmountValue)
    {
        config([
            'toman.currency' => $configCurrency,
        ]);

        Http::fake([
            'https://api.idpay.ir/v1.1/payment' => Http::response([
                'id' => 'tid1000',
                'link' => 'https://idpay.ir/p/ws-sandbox/tid1000',
            ], 201),
        ]);

        $this->factory->amount($actualAmount)->request();

        Http::assertSent(function (Request $request) use ($expectedAmountValue) {
            return $request['amount'] == $expectedAmountValue;
        });
    }
}
