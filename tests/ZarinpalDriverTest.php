<?php


namespace AmirrezaNasiri\LaravelToman\Tests;


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
        $this->mockPurchaseResponse();

        $gateway = new ZarinpalGateway([
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ]);

        $gateway->callback('https://example.com/verify-here')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        $paymentRequest = $gateway->request();

        self::assertEquals('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json', $this->getLastRequestURL());
        $this->assertLastRequestedDataEquals([
            "MerchantID" => "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
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
        $this->mockPurchaseResponse();

        $gateway = new ZarinpalGateway([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ]);

        $gateway->callback('https://example.com/verify-here')
            ->amount(1500)
            ->description('An awesome payment gateway!')
            ->data('Mobile', '09350000000')
            ->email('amirreza@example.com');

        $paymentRequest = $gateway->request();

        self::assertEquals('https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentRequest.json', $this->getLastRequestURL());
        $this->assertLastRequestedDataEquals([
            "MerchantID" => "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
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
        $this->realizeClient();

        $gateway = new ZarinpalGateway([
            'sandbox' => true,
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        ]);

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
        $this->mockClient(new Response(404, [], json_encode([
            'Status' => -11,
            'Authority' => '',
            'errors' => [
                'CallbackURL' => 'The callback u r l field is required.',
                'Email' => 'The email must be a valid email address.',
            ]
        ])));

        $this->expectException( GatewayException::class );

        $this->getConfiguredDriver()->request();

        /** @var GatewayValidationException $exception */
        $exception = $this->getExpectedException();

        self::assertEquals(-11, $exception->getCode());
        self::assertContains('callback', $exception->getMessage());
    }

    /**
     * @test
     * @dataProvider errorStatusProvider
     */
    public function converts_other_gateway_errors_to_exception($status, $partialMessage)
    {
        $this->mockClient(new Response(404, [], json_encode([
            'Status' => $status,
            'Authority' => '',
        ])));

        try {
            $this->getConfiguredDriver()->request();
        } catch (GatewayException $exception) {
            self::assertEquals($status, $exception->getCode());
            self::assertContains($partialMessage, $exception->getMessage());
            return true;
        }

        self::fail('No GatewayException is thrown!');
    }

    public function errorStatusProvider()
    {
        return [
            ['-1', 'ناقص'],
            ['-2', 'مرچنت'],
            ['-3', 'محدوديت هاي شاپرك'],
            ['-4', 'سطح تاييد'],
            ['-40', "دسترسي به متد"],
            ['-41', 'AdditionalData'],
            ['-42', 'طول عمر'],
            ['-99999', 'unknown'],
        ];
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        $this->mockPurchaseResponse(3);

        $this->getConfiguredDriver()->setConfig(['merchant_id' => '1111-1111-1111-1111'])->request();
        $this->assertDataInRequest('1111-1111-1111-1111', 'MerchantID');

        $this->getConfiguredDriver()->merchant('2222-2222-2222-2222')->request();
        $this->assertDataInRequest('2222-2222-2222-2222', 'MerchantID');

        $this->getConfiguredDriver()->data('MerchantID', '3333-3333-3333-3333')->request();
        $this->assertDataInRequest('3333-3333-3333-3333', 'MerchantID');
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        $this->mockPurchaseResponse(2);
        $this->getConfiguredDriver()->amount(1000)->request();
        $this->assertDataInRequest(1000, 'Amount');


        $this->getConfiguredDriver()->data('Amount', 3500)->request();
        $this->assertDataInRequest(3500, 'Amount');
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        $this->app['router']->get('/verify-payment')->name('payment.callback');

        $this->mockPurchaseResponse(3);

        config(['toman.callback_route' => 'payment.callback']);
        $this->getConfiguredDriver()->request();
        $this->assertDataInRequest(URL::route('payment.callback'), 'CallbackURL');

        $this->getConfiguredDriver()->callback('https://example.com/callbackA')->request();
        $this->assertDataInRequest('https://example.com/callbackA', 'CallbackURL');

        $this->getConfiguredDriver()->data('CallbackURL', 'https://example.com/callbackB')->request();
        $this->assertDataInRequest('https://example.com/callbackB', 'CallbackURL');

        config(['toman.callback_route' => null]);
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        $this->mockPurchaseResponse(4);

        config(['toman.description' => 'Paying :amount for invoice']);
        $this->getConfiguredDriver()->amount(5000)->request();
        $this->assertDataInRequest('Paying 5000 for invoice', 'Description');

        $this->getConfiguredDriver()->description('Some descriptions')->request();
        $this->assertDataInRequest('Some descriptions', 'Description');

        $this->getConfiguredDriver()->data('Description', 'Other text')->request();
        $this->assertDataInRequest('Other text', 'Description');

        config(['toman.description' => null]);

        $this->getConfiguredDriver()->request();
        $this->assertDataInRequest(null, 'Description');
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        $this->mockPurchaseResponse(2);

        $this->getConfiguredDriver()->mobile('09350000000')->request();
        $this->assertDataInRequest('09350000000', 'Mobile');

        $this->getConfiguredDriver()->data('Mobile', '09351111111')->request();
        $this->assertDataInRequest('09351111111', 'Mobile');
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        $this->mockPurchaseResponse(2);

        $this->getConfiguredDriver()->email('amirreza@example.com')->request();
        $this->assertDataInRequest('amirreza@example.com', 'Email');

        $this->getConfiguredDriver()->data('Email', 'alireza@example.com')->request();
        $this->assertDataInRequest('alireza@example.com', 'Email');
    }

    /** @test */
    public function can_set_config_explicitly()
    {
        $gateway = new ZarinpalGateway(['keyA' => 'valueA']);
        self::assertEquals(['keyA' => 'valueA'], $gateway->getConfig());

        $gateway->setConfig(['keyB' => 'valueB']);
        self::assertEquals(['keyB' => 'valueB'], $gateway->getConfig());
    }

    /**
     * @test
     * @dataProvider sandboxProvider
     */
    public function validates_sandbox($passes, $sandbox)
    {
        $this->mockPurchaseResponse();

        $gateway = new ZarinpalGateway($this->validConfig([
            'sandbox' => $sandbox
        ]));

        try {
            $gateway->request();
            self::assertTrue($passes);
        } catch (InvalidConfigException $exception) {
            self::assertFalse($passes);
            self::assertContains('sandbox', $exception->getMessage());
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

    private function getConfiguredDriver()
    {
        return new ZarinpalGateway($this->validConfig());
    }

    private function validConfig($overridden = [])
    {
        return array_merge([
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        ], $overridden);
    }

    private function mockPurchaseResponse($times = 1)
    {
        $responses = [];
        foreach (range(1, $times) as $i) {
            $responses[] = new Response(200, [], json_encode([
                'Status' => 100,
                'Authority' => '0000012345',
            ]));
        }

        $this->mockClient($responses);
    }
}
