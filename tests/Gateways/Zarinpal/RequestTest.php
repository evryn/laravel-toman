<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
use Evryn\LaravelToman\Gateways\Zarinpal\RequestedPayment;
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
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::endpointProvider()
     */
    public function can_request_payment(bool $sandbox, string $baseUrl)
    {
        // By sending a request to create a new ZarinPal transaction,
        // we need to ensure that it sends request to proper endpoint, with correct
        // data and amount.
        // We also need to check that created payment can be redirected to default gateway
        // or the specific one.

        config([
            'toman.currency' => 'toman',
        ]);

        Http::fake([
            "$baseUrl/pg/rest/WebGate/PaymentRequest.json" => Http::response([
                'Status' => '100',
                'Authority' => 'A0000012345',
            ], 200),
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx',
        ]);

        $gateway = $this->factory
            ->callback('https://example.com/callback')
            ->amount(1500)
            ->description('Payment for :amount.')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        tap($gateway->request(), function (RequestedPayment $request) use ($baseUrl) {
            Http::assertSent(function (Request $request) use ($baseUrl) {
                return $request->method() === 'POST'
                    && $request->url() === "https://$baseUrl/pg/rest/WebGate/PaymentRequest.json"
                    && $request['MerchantID'] === 'xxxx-xxxx-xxxx-xxxx'
                    && $request['Amount'] == 1500
                    && $request['CallbackURL'] === 'https://example.com/callback'
                    && $request['Description'] === 'Payment for 1500.'
                    && $request['Mobile'] === '09350000000'
                    && $request['Email'] === 'amirreza@example.com';
            });

            // Since request is successful, we need to ensure that nothing can be
            // thrown and determiners are correct
            $request->throw();
            self::assertTrue($request->successful());
            self::assertFalse($request->failed());

            self::assertEquals('A0000012345', $request->transactionId());

            $redirectDefault = $request->pay();
            self::assertInstanceOf(RedirectResponse::class, $redirectDefault);
            self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345", $redirectDefault->getTargetUrl());
            self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345", $request->paymentUrl());

            $redirectSpecific = $request->pay(['gateway' => 'example']);
            self::assertInstanceOf(RedirectResponse::class, $redirectSpecific);
            self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345/example", $redirectSpecific->getTargetUrl());
            self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345/example", $request->paymentUrl(['gateway' => 'example']));
        });
    }

    /** @test */
    public function fails_with_server_error()
    {
        // When requesting a valid payment result in 5xx error, we consider this as an
        // issue in gateway-side (server) and expect an expect API to provide proper results

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response(null, 555),
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
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::clientErrorProvider()
     */
    public function fails_with_client_error_without_message($httpStatus, $statusCode, $messageKey)
    {
        // When requesting a valid payment result in 4xx error, but there is nothing in errors data,
        // we consider this as an issue in merchant-side (client) and expect an expect API to provide
        // proper results.

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
            ], $httpStatus),
        ]);

        tap($this->factory->request(), function (RequestedPayment $request) use ($statusCode, $messageKey) {
            self::assertFalse($request->successful());
            self::assertTrue($request->failed());

            try {
                $request->throw();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
                self::assertEquals($statusCode, $exception->getCode());
                self::assertEquals($exception->getCode(), $request->status());

                self::assertEquals(__("toman::zarinpal.status.$messageKey"), $exception->getMessage());
                self::assertEquals(__("toman::zarinpal.status.$messageKey"), $request->message());
                self::assertEquals([__("toman::zarinpal.status.$messageKey")], $request->messages());
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
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::clientErrorProvider()
     */
    public function fails_with_client_error_with_message($httpStatus, $statusCode, $messageKey)
    {
        // When requesting a valid payment result in 4xx error, with given error messages,
        // we consider this as an issue in merchant-side (client) and expect an expect API
        // to provide proper results

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
                'errors' => [
                    'Email' => [
                        'The email must be a valid email address.',
                    ],
                    'Amount' => [
                        'The amount must be valid.',
                    ],
                ],
            ], $httpStatus),
        ]);

        tap($this->factory->request(), function (RequestedPayment $request) use ($statusCode, $messageKey) {
            self::assertFalse($request->successful());
            self::assertTrue($request->failed());

            try {
                $request->throw();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayClientException $exception) {
                self::assertEquals($statusCode, $exception->getCode());
                self::assertEquals($exception->getCode(), $request->status());

                self::assertEquals(__("toman::zarinpal.status.$messageKey"), $exception->getMessage());
                self::assertEquals('The email must be a valid email address.', $request->message());
                self::assertEquals([
                    'Email' => ['The email must be a valid email address.'],
                    'Amount' => ['The amount must be valid.'],
                ], $request->messages());
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
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::tomanBasedAmountProvider()
     */
    public function can_set_amount_in_different_currencies($configCurrency, $actualAmount, $expectedAmountValue)
    {
        config([
            'toman.currency' => $configCurrency,
        ]);

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => '100',
                'Authority' => 'A0000012345',
            ], 200),
        ]);

        $this->factory->amount($actualAmount)->request();

        Http::assertSent(function (Request $request) use ($expectedAmountValue) {
            return $request['Amount'] == $expectedAmountValue;
        });
    }
}
