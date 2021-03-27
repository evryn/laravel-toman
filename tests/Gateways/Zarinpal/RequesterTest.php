<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Gateways\Zarinpal\Requester;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

final class RequesterTest extends TestCase
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

        $gateway = new Requester([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ]);

        $gateway->callback('https://example.com/callback')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        $paymentRequest = $gateway->request();

        Http::assertSent(function (Request $request) use ($baseUrl) {
            return $request->url() === "https://$baseUrl/pg/rest/WebGate/PaymentRequest.json"
                && $request['MerchantID'] === 'xxxx-xxxx-xxxx-xxxx'
                && $request['Amount'] == 1500
                && $request['CallbackURL'] === 'https://example.com/callback'
                && $request['Description'] === 'An awesome payment gateway!'
                && $request['Mobile'] === '09350000000'
                && $request['Email'] === 'amirreza@example.com';
        });

        self::assertEquals('A0000012345', $paymentRequest->getTransactionId());

        self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345", $paymentRequest->getPaymentUrl());
        self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345/example", $paymentRequest->getPaymentUrl(['gateway' => 'example']));

        $redirectDefault = $paymentRequest->pay();
        self::assertInstanceOf(RedirectResponse::class, $redirectDefault);
        self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345", $redirectDefault->getTargetUrl());

        $redirectSpecific = $paymentRequest->pay(['gateway' => 'example']);
        self::assertInstanceOf(RedirectResponse::class, $redirectSpecific);
        self::assertEquals("https://$baseUrl/pg/StartPay/A0000012345/example", $redirectSpecific->getTargetUrl());
    }

    /** @test */
    public function throws_server_exception_in_case_of_gateway_issue()
    {
        // When requesting a valid payment result in 5xx error, we consider this as an
        // issue in gateway-side (server) and expect an exception to be thrown containing
        // HTTP status code.

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response(null, 555),
        ]);

        $gateway = new Requester([
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ]);

        $this->expectException(GatewayServerException::class);

        $gateway->callback('https://example.com/callback')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com')
            ->request();

        self::assertEquals(555, $this->getExpectedException()->getCode());
    }

    public function clientErrorProvider()
    {
        return [
            [200, Status::INCOMPLETE_DATA], // 404 HTTP code is not guaranteed for errors
            [404, Status::INCOMPLETE_DATA],
            [404, Status::WRONG_IP_OR_MERCHANT_ID],
            [404, Status::SHAPARAK_LIMITED],
            [404, Status::INSUFFICIENT_USER_LEVEL],
            [404, Status::REQUEST_NOT_FOUND],
            [404, Status::UNABLE_TO_EDIT_REQUEST],
            [404, Status::NO_FINANCIAL_OPERATION],
            [404, Status::FAILED_TRANSACTION],
            [404, Status::AMOUNTS_NOT_EQUAL],
            [404, Status::TRANSACTION_SPLITTING_LIMITED],
            [404, Status::METHOD_ACCESS_DENIED],
            [404, Status::INVALID_ADDITIONAL_DATA],
            [404, Status::INVALID_EXPIRATION_RANGE],
            [404, Status::REQUEST_ARCHIVED],
            [404, Status::OPERATION_SUCCEED],
            [404, Status::UNEXPECTED],
        ];
    }

    /**
     * @test
     * @dataProvider clientErrorProvider
     */
    public function throws_client_exception_with_default_messages_if_no_error_is_given($httpStatus, $statusCode)
    {
        // When requesting a valid payment result in 4xx error, but there is nothing in errors data,
        // we consider this as an issue in merchant-side (client) and expect an exception to be thrown
        // containing real error code with default message of that code.

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
            ], $httpStatus),
        ]);

        $this->expectException(GatewayClientException::class);

        $this->validRequester()->request();

        self::assertEquals($statusCode, $this->getExpectedException()->getCode());
        self::assertEquals(__("toman::zarinpal.status.$statusCode"), $this->getExpectedException()->getMessage());
    }

    /**
     * @test
     * @dataProvider clientErrorProvider
     */
    public function throws_client_exception_with_first_given_error_message($httpStatus, $statusCode)
    {
        // When requesting a valid payment result in 4xx error, with given error messages,
        // we consider this as an issue in merchant-side (client) and expect an exception to be thrown
        // containing real error code and first error message.

        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json' => Http::response([
                'Status' => $statusCode,
                'errors' => [
                    'Email' => 'The email must be a valid email address.',
                ]
            ], $httpStatus),
        ]);

        $this->expectException(GatewayClientException::class);

        $this->validRequester()->request();

        self::assertEquals($statusCode, $this->getExpectedException()->getCode());
        self::assertEquals('The email must be a valid email address.', $this->getExpectedException()->getMessage());
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        // We need to ensure that developer can set merchant id elegantly, with their preferred methods

        $this->fakeValidResponse();

        (new Requester($this->validConfig(['merchant_id' => '11111'])))->request();
        Http::assertNthRequestFieldEquals('11111', 'MerchantID', 1);

        (new Requester($this->validConfig()))->data('MerchantID', '33333')->request();
        Http::assertNthRequestFieldEquals('33333', 'MerchantID', 2);

        (new Requester($this->validConfig()))->merchantId('44444')->request();
        Http::assertNthRequestFieldEquals('44444', 'MerchantID', 3);
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        // We need to ensure that developer can set callback url elegantly, from configured route
        // or with their preferred methods

        $this->fakeValidResponse();

        $this->app['router']->get('/callback')->name('payment.callback');
        config(['toman.callback_route' => 'payment.callback']);
        (new Requester($this->validConfig()))->request();
        Http::assertNthRequestFieldEquals(URL::route('payment.callback'), 'CallbackURL', 1);

        (new Requester($this->validConfig()))->data('CallbackURL', 'https://example.com/callback')->request();
        Http::assertNthRequestFieldEquals('https://example.com/callback', 'CallbackURL', 2);

        (new Requester($this->validConfig()))->callback('https://example.com/callback')->request();
        Http::assertNthRequestFieldEquals('https://example.com/callback', 'CallbackURL', 3);
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        // We need to ensure that developer can set amount elegantly, with their preferred methods

        $this->fakeValidResponse();

        (new Requester($this->validConfig()))->data('Amount', '10000')->request();
        Http::assertNthRequestFieldEquals('10000', 'Amount', 1);

        (new Requester($this->validConfig()))->amount(20000)->request();
        Http::assertNthRequestFieldEquals(20000, 'Amount', 2);
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        // We need to ensure that developer can set amount elegantly, from configured description
        // or with their preferred methods

        $this->fakeValidResponse();

        config(['toman.description' => 'Paying :amount for invoice']);
        (new Requester($this->validConfig()))->amount(5000)->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 1);

        (new Requester($this->validConfig()))->amount(5000)->data('Description', 'Paying :amount for invoice')->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 2);

        (new Requester($this->validConfig()))->amount(5000)->description('Paying :amount for invoice')->request();
        Http::assertNthRequestFieldEquals('Paying 5000 for invoice', 'Description', 3);
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        // We need to ensure that developer can set mobile elegantly, with their preferred methods

        $this->fakeValidResponse();

        (new Requester($this->validConfig()))->data('Mobile', '09350000000')->request();
        Http::assertNthRequestFieldEquals('09350000000', 'Mobile', 1);

        (new Requester($this->validConfig()))->mobile('09350000000')->request();
        Http::assertNthRequestFieldEquals('09350000000', 'Mobile', 2);
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        // We need to ensure that developer can set email elegantly, with their preferred methods

        $this->fakeValidResponse();

        (new Requester($this->validConfig()))->data('Email', 'amirreza@example.com')->request();
        Http::assertNthRequestFieldEquals('amirreza@example.com', 'Email', 1);

        (new Requester($this->validConfig()))->email('amirreza@example.com')->request();
        Http::assertNthRequestFieldEquals('amirreza@example.com', 'Email', 2);
    }

    private function validConfig($overridden = []): array
    {
        return array_merge([
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ], $overridden);
    }

    private function validRequester(): Requester
    {
        return (new Requester([
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ]))
            ->callback('https://example.com/callback')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');
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
}
