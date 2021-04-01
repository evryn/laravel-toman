<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\RequestedPayment;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

final class RequestTest extends TestCase
{
    public static function endpointProvider()
    {
        return [
            'Sandbox' => [true, 'sandbox.zarinpal.com'],
            'Production' => [false, 'www.zarinpal.com'],
        ];
    }

    /**
     * @test
     * @dataProvider endpointProvider
     */
    public function can_request_payment(bool $sandbox, string $baseUrl)
    {
        // By sending a request to create a new ZarinPal transaction,
        // we need to ensure that it sends request to proper endpoint, with correct
        // data and amount.
        // We also need to check that created payment can be redirected to default gateway
        // or the specific one.

        Http::fake([
            "$baseUrl/pg/rest/WebGate/PaymentRequest.json" => Http::response([
                'Status' => '100',
                'Authority' => 'A0000012345',
            ], 200),
        ]);

        $gateway = $this->gateway([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ])
            ->callback('https://example.com/callback')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        tap($gateway->request(), function (RequestedPayment $request) use ($baseUrl) {

            Http::assertSent(function (Request $request) use ($baseUrl) {
                return $request->method() === 'POST'
                    && $request->url() === "https://$baseUrl/pg/rest/WebGate/PaymentRequest.json"
                    && $request['MerchantID'] === 'xxxx-xxxx-xxxx-xxxx'
                    && $request['Amount'] == 1500
                    && $request['CallbackURL'] === 'https://example.com/callback'
                    && $request['Description'] === 'An awesome payment gateway!'
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
    public function fails_with_gateway_exception()
    {
        // When requesting a valid payment result in 5xx error, we consider this as an
        // issue in gateway-side (server) and expect an expect API to provide proper results

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response(null, 555),
        ]);

        tap($this->configuredGateway()->request(), function (RequestedPayment $request) {
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
            } catch (GatewayServerException $exception) {}

            try {
                $request->paymentUrl();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {}

            try {
                $request->pay();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {}
        });
    }

    public function clientErrorProvider()
    {
        return [
            [200, Status::INCOMPLETE_DATA, 'incomplete_data'], // 404 HTTP code is not guaranteed for errors
            [404, Status::INCOMPLETE_DATA, 'incomplete_data'],
            [404, Status::WRONG_IP_OR_MERCHANT_ID, 'wrong_ip_or_merchant_id'],
            [404, Status::SHAPARAK_LIMITED, 'shaparak_limited'],
            [404, Status::INSUFFICIENT_USER_LEVEL, 'insufficient_user_level'],
            [404, Status::REQUEST_NOT_FOUND, 'request_not_found'],
            [404, Status::UNABLE_TO_EDIT_REQUEST, 'unable_to_edit_request'],
            [404, Status::NO_FINANCIAL_OPERATION, 'no_financial_operation'],
            [404, Status::FAILED_TRANSACTION, 'failed_transaction'],
            [404, Status::AMOUNTS_NOT_EQUAL, 'amounts_not_equal'],
            [404, Status::TRANSACTION_SPLITTING_LIMITED, 'transaction_splitting_limited'],
            [404, Status::METHOD_ACCESS_DENIED, 'method_access_denied'],
            [404, Status::INVALID_ADDITIONAL_DATA, 'invalid_additional_data'],
            [404, Status::INVALID_EXPIRATION_RANGE, 'invalid_expiration_range'],
            [404, Status::REQUEST_ARCHIVED, 'request_archived'],
            [404, Status::OPERATION_SUCCEED, 'operation_succeed'],
            [404, Status::UNEXPECTED, 'unexpected'],
        ];
    }

    /**
     * @test
     * @dataProvider clientErrorProvider
     */
    public function fails_with_client_exception_without_message($httpStatus, $statusCode, $messageKey)
    {
        // When requesting a valid payment result in 4xx error, but there is nothing in errors data,
        // we consider this as an issue in merchant-side (client) and expect an expect API to provide
        // proper results.

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
            ], $httpStatus),
        ]);

        tap($this->configuredGateway()->request(), function (RequestedPayment $request) use ($statusCode, $messageKey) {
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
            } catch (GatewayClientException $exception) {}

            try {
                $request->paymentUrl();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {}

            try {
                $request->pay();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {}
        });
    }

    /**
     * @test
     * @dataProvider clientErrorProvider
     */
    public function fails_with_client_exception_with_message($httpStatus, $statusCode, $messageKey)
    {
        // When requesting a valid payment result in 4xx error, with given error messages,
        // we consider this as an issue in merchant-side (client) and expect an expect API
        // to provide proper results

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
                'errors' => [
                    'Email' => 'The email must be a valid email address.',
                    'Amount' => 'The amount must be valid.',
                ]
            ], $httpStatus),
        ]);

        tap($this->configuredGateway()->request(), function (RequestedPayment $request) use ($statusCode, $messageKey) {
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
                    'Email' => 'The email must be a valid email address.',
                    'Amount' => 'The amount must be valid.'
                ], $request->messages());
            }

            try {
                $request->transactionId();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {}

            try {
                $request->paymentUrl();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {}

            try {
                $request->pay();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {}
        });
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        // We need to ensure that developer can set merchant id elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->gateway(['merchant_id' => '11111'])->request();
        Http::assertNthRequestFieldEquals('11111', 'MerchantID', 1);

        $this->configuredGateway()->data('MerchantID', '33333')->request();
        Http::assertNthRequestFieldEquals('33333', 'MerchantID', 2);

        $this->configuredGateway()->merchantId('44444')->request();
        Http::assertNthRequestFieldEquals('44444', 'MerchantID', 3);
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        // We need to ensure that developer can set callback url elegantly, from configured route
        // or with their preferred methods

        $this->fakeValidResponse();

        $this->app['router']->get('/callback1')->name('payment.callback');
        config(['toman.callback_route' => 'payment.callback']);
        $this->configuredGateway()->request();
        Http::assertNthRequestFieldEquals(URL::route('payment.callback'), 'CallbackURL', 1);

        $this->configuredGateway()->data('CallbackURL', 'https://example.com/callback2')->request();
        Http::assertNthRequestFieldEquals('https://example.com/callback2', 'CallbackURL', 2);

        $this->configuredGateway()->callback('https://example.com/callback3')->request();
        Http::assertNthRequestFieldEquals('https://example.com/callback3', 'CallbackURL', 3);
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        // We need to ensure that developer can set amount elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->configuredGateway()->data('Amount', '10000')->request();
        Http::assertNthRequestFieldEquals('10000', 'Amount', 1);

        $this->configuredGateway()->amount(20000)->request();
        Http::assertNthRequestFieldEquals(20000, 'Amount', 2);
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        // We need to ensure that developer can set amount elegantly, from configured description
        // or with their preferred methods

        $this->fakeValidResponse();

        config(['toman.description' => 'Paying :amount for invoice']);
        $this->configuredGateway()->amount(5000)->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 1);

        $this->configuredGateway()->amount(5000)->data('Description', 'Paying :amount for invoice')->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 2);

        $this->configuredGateway()->amount(5000)->description('Paying :amount for invoice')->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 3);
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        // We need to ensure that developer can set mobile elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->configuredGateway()->data('Mobile', '09350000000')->request();
        Http::assertNthRequestFieldEquals('09350000000', 'Mobile', 1);

        $this->configuredGateway()->mobile('09350000000')->request();
        Http::assertNthRequestFieldEquals('09350000000', 'Mobile', 2);
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        // We need to ensure that developer can set email elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->configuredGateway()->data('Email', 'amirreza@example.com')->request();
        Http::assertNthRequestFieldEquals('amirreza@example.com', 'Email', 1);

        $this->configuredGateway()->email('amirreza@example.com')->request();
        Http::assertNthRequestFieldEquals('amirreza@example.com', 'Email', 2);
    }

    private function fakeValidResponse(): void
    {
        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => '100',
                'Authority' => 'A0000012345',
            ], 200),
        ]);
    }

    private function configuredGateway(): PendingRequest
    {
        return $this->gateway([
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ]);
    }

    private function gateway($config = []): PendingRequest
    {
        return (new Factory($this->app))->newPendingRequest('zarinpal', $config);
    }
}
