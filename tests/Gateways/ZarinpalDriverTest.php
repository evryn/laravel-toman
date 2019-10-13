<?php


namespace AmirrezaNasiri\LaravelToman\Tests\Gateways;


use AmirrezaNasiri\LaravelToman\Clients\GuzzleClient;
use AmirrezaNasiri\LaravelToman\Gateways\ZarinpalGateway;
use AmirrezaNasiri\LaravelToman\Exceptions\GatewayException;
use AmirrezaNasiri\LaravelToman\Exceptions\InvalidConfigException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;


final class ZarinpalDriverTest extends DriverTestCase
{
    /** @test */
    public function can_request_production_payment()
    {
        $gateway = ZarinpalGateway::make([
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx',
        ], $this->mockedPurchaseResponse());

        $gateway->callback('https://example.com/verify-here')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        $paymentRequest = $gateway->request();

        $this->assertLastRequestedUrlEquals('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json');
        $this->assertLastRequestedDataEquals([
            "MerchantID" => "xxxx-xxxx-xxxx-xxxx",
            "Amount" => 1500,
            "CallbackURL" => 'https://example.com/verify-here',
            "Description" => 'An awesome payment gateway!',
            "Mobile" => '09350000000',
            "Email" => 'amirreza@example.com',
        ]);

        self::assertEquals('0000012345', $paymentRequest->getTransactionId());
        self::assertEquals("https://www.zarinpal.com/pg/StartPay/0000012345", $paymentRequest->getPaymentUrl());

        $redirect = $paymentRequest->pay();

        self::assertInstanceOf(RedirectResponse::class, $redirect);
        self::assertEquals("https://www.zarinpal.com/pg/StartPay/0000012345", $redirect->getTargetUrl());
    }

    /** @test */
    public function can_request_sandbox_payment()
    {
        $gateway = ZarinpalGateway::make([
            'sandbox' => true,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx',
        ], $this->mockedPurchaseResponse());

        $gateway->callback('https://example.com/verify-here')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        $paymentRequest = $gateway->request();

        $this->assertLastRequestedUrlEquals('https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentRequest.json');
        $this->assertLastRequestedDataEquals([
            "MerchantID" => "xxxx-xxxx-xxxx-xxxx",
            "Amount" => 1500,
            "CallbackURL" => 'https://example.com/verify-here',
            "Description" => 'An awesome payment gateway!',
            "Mobile" => '09350000000',
            "Email" => 'amirreza@example.com',
        ]);

        self::assertEquals('0000012345', $paymentRequest->getTransactionId());
        self::assertEquals("https://sandbox.zarinpal.com/pg/StartPay/0000012345", $paymentRequest->getPaymentUrl());

        $redirect = $paymentRequest->pay();

        self::assertInstanceOf(RedirectResponse::class, $redirect);
        self::assertEquals("https://sandbox.zarinpal.com/pg/StartPay/0000012345", $redirect->getTargetUrl());
    }

    /**
     * @group external
     * @test
     */
    public function can_request_a_real_sandbox_payment()
    {
        $gateway = ZarinpalGateway::make([
            'sandbox' => true,
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        ], app(GuzzleClient::class));

        $gateway->callback('https://example.com/verify-here')
            ->amount(1500)
            ->description('My description!')
            ->data('Email', 'amirreza@example.com')
            ->mobile('09350000000');

        self::assertGreaterThanOrEqual(3, strlen($gateway->request()->getTransactionId()));
    }

    /** @test */
    public function converts_validation_error_to_exception()
    {
        $client = $this->mockedGuzzleClient(new Response(404, [], json_encode([
            'Status' => -11,
            'Authority' => '',
            'errors' => [
                'CallbackURL' => 'The callback u r l field is required.',
                'Email' => 'The email must be a valid email address.',
            ]
        ])));

        $this->expectException( GatewayException::class );

        ZarinpalGateway::make($this->validConfig(), $client)->request();

        $exception = $this->getExpectedException();

        self::assertEquals(-11, $exception->getCode());
        self::assertStringContainsString('callback', $exception->getMessage());
    }

    /**
     * @test
     * @dataProvider errorStatusProvider
     */
    public function converts_other_gateway_errors_to_exception($httpCode, $status, $partialMessage)
    {
        $client = $this->mockedGuzzleClient(new Response($httpCode, [], json_encode([
            'Status' => $status,
            'Authority' => '',
        ])));

        try {
            ZarinpalGateway::make($this->validConfig(), $client)->request();
        } catch (GatewayException $exception) {
            self::assertEquals($status, $exception->getCode());
            self::assertStringContainsString($partialMessage, $exception->getMessage());
            return true;
        }

        self::fail('No GatewayException is thrown!');
    }

    public function errorStatusProvider()
    {
        return [
            [404, '-1', 'ناقص'],
            [404, '-2', 'مرچنت'],
            [404, '-3', 'محدوديت هاي شاپرك'],
            [404, '-4', 'سطح تاييد'],
            [404, '-40', "دسترسي به متد"],
            [404, '-41', 'AdditionalData'],
            [404, '-42', 'طول عمر'],
            [404, '-99999', 'unknown'],
            [200, '-1', 'ناقص'], // In case of switching 404 on failure since it's not been documented
        ];
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        ZarinpalGateway::make($this->validConfig(['merchant_id' => '1111-1111-1111-1111']), $client)
            ->request();
        $this->assertDataInRequest('1111-1111-1111-1111', 'MerchantID');

        ZarinpalGateway::make($this->validConfig(['merchant_id' => '1111-1111-1111-1111']), $client)
            ->data('MerchantID', '2222-2222-2222-2222')
            ->request();
        $this->assertDataInRequest('2222-2222-2222-2222', 'MerchantID');
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        ZarinpalGateway::make($this->validConfig(), $client)->amount(1000)->request();
        $this->assertDataInRequest(1000, 'Amount');

        ZarinpalGateway::make($this->validConfig(), $client)->data('Amount', 3500)->request();
        $this->assertDataInRequest(3500, 'Amount');
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        $this->app['router']->get('/verify-payment')->name('payment.callback');

        $client = $this->mockedPurchaseResponse(3);

        config(['toman.callback_route' => 'payment.callback']);
        ZarinpalGateway::make($this->validConfig(), $client)->request();
        $this->assertDataInRequest(URL::route('payment.callback'), 'CallbackURL');

        ZarinpalGateway::make($this->validConfig(), $client)->callback('https://example.com/callbackA')->request();
        $this->assertDataInRequest('https://example.com/callbackA', 'CallbackURL');

        ZarinpalGateway::make($this->validConfig(), $client)->data('CallbackURL', 'https://example.com/callbackB')->request();
        $this->assertDataInRequest('https://example.com/callbackB', 'CallbackURL');
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        $client = $this->mockedPurchaseResponse(4);

        config(['toman.description' => 'Paying :amount for invoice']);
        ZarinpalGateway::make($this->validConfig(), $client)->amount(5000)->request();
        $this->assertDataInRequest('Paying 5000 for invoice', 'Description');

        ZarinpalGateway::make($this->validConfig(), $client)->description('Some descriptions')->request();
        $this->assertDataInRequest('Some descriptions', 'Description');

        ZarinpalGateway::make($this->validConfig(), $client)->data('Description', 'Other text')->request();
        $this->assertDataInRequest('Other text', 'Description');
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        ZarinpalGateway::make($this->validConfig(), $client)->mobile('09350000000')->request();
        $this->assertDataInRequest('09350000000', 'Mobile');

        ZarinpalGateway::make($this->validConfig(), $client)->data('Mobile', '09351111111')->request();
        $this->assertDataInRequest('09351111111', 'Mobile');
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        ZarinpalGateway::make($this->validConfig(), $client)->email('amirreza@example.com')->request();
        $this->assertDataInRequest('amirreza@example.com', 'Email');

        ZarinpalGateway::make($this->validConfig(), $client)->data('Email', 'alireza@example.com')->request();
        $this->assertDataInRequest('alireza@example.com', 'Email');
    }

    /**
     * @test
     * @dataProvider sandboxProvider
     */
    public function validates_sandbox($passes, $sandbox)
    {
        $client = $this->mockedPurchaseResponse();

        $gateway = ZarinpalGateway::make($this->validConfig([
            'sandbox' => $sandbox
        ]), $client);

        try {
            $gateway->request();
            self::assertTrue($passes);
        } catch (InvalidConfigException $exception) {
            self::assertFalse($passes);
            self::assertStringContainsString('sandbox', $exception->getMessage());
        }
    }

    public function sandboxProvider()
    {
        return [
            "Passes without value" => [true, null],
            "Passes with false" => [true, false],
            "Passes with true" => [true, true],
            "Fails with string" => [false, 'true'],
            "Fails with array" => [false, ['true']],
        ];
    }

    private function validConfig($overridden = [])
    {
        return array_merge([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ], $overridden);
    }

    private function mockedPurchaseResponse($times = 1)
    {
        $responses = [];
        foreach (range(1, $times) as $i) {
            $responses[] = new Response(200, [], json_encode([
                'Status' => 100,
                'Authority' => '0000012345',
            ]));
        }

        return $this->mockedGuzzleClient($responses);
    }
}
