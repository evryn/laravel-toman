<?php


namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;


use Evryn\LaravelToman\Clients\GuzzleClient;
use Evryn\LaravelToman\Gateways\Zarinpal\Requester;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Exceptions\InvalidConfigException;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Tests\Gateways\DriverTestCase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;


final class RequesterTest extends DriverTestCase
{
    /** @test */
    public function can_request_production_payment()
    {
        $gateway = Requester::make([
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
        $gateway = Requester::make([
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
        $gateway = Requester::make([
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
        $this->expectExceptionMessage('The callback u r l field is required.');

        Requester::make($this->validConfig(), $client)->request();

        $exception = $this->getExpectedException();

        self::assertEquals(-11, $exception->getCode());
        self::assertContains('callback', $exception->getMessage());
    }

    /**
     * @test
     * @dataProvider errorProvider
     */
    public function converts_other_gateway_errors_to_exception($passes, $httpCode, $status)
    {
        $client = $this->mockedGuzzleClient(new Response($httpCode, [], json_encode([
            'Status' => $status,
            'Authority' => '',
        ])));

        try {
            Requester::make($this->validConfig(), $client)->request();
            self::assertTrue($passes);
        } catch (GatewayException $exception) {
            self::assertEquals($status, $exception->getCode());
            self::assertEquals(Status::toMessage($status), $exception->getMessage());
            return true;
        }

        if (!$passes) {
            self::fail('No GatewayException is thrown!');
        }
    }

    public function errorProvider()
    {
        return [
            [true, 200, Status::OPERATION_SUCCEED],
            [false, 404, Status::INCOMPLETE_DATA],
            [false, 200, Status::INCOMPLETE_DATA], // 404 HTTP code is not guaranteed in documents
            [false, 404, Status::WRONG_IP_OR_MERCHANT_ID],
            [false, 404, Status::SHAPARAK_LIMITED],
            [false, 404, Status::INSUFFICIENT_USER_LEVEL],
            [false, 404, Status::REQUEST_NOT_FOUND],
            [false, 404, Status::UNABLE_TO_EDIT_REQUEST],
            [false, 404, Status::NO_FINANCIAL_OPERATION],
            [false, 404, Status::FAILED_TRANSACTION],
            [false, 404, Status::AMOUNTS_NOT_EQUAL],
            [false, 404, Status::TRANSACTION_SPLITTING_LIMITED],
            [false, 404, Status::METHOD_ACCESS_DENIED],
            [false, 404, Status::INVALID_ADDITIONAL_DATA],
            [false, 404, Status::INVALID_EXPIRATION_RANGE],
            [false, 404, Status::REQUEST_ARCHIVED],
            [false, 404, Status::OPERATION_SUCCEED],
            [false, 404, Status::UNEXPECTED],
        ];
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        Requester::make($this->validConfig(['merchant_id' => '1111-1111-1111-1111']), $client)
            ->request();
        $this->assertDataInRequest('1111-1111-1111-1111', 'MerchantID');

        Requester::make($this->validConfig(['merchant_id' => '1111-1111-1111-1111']), $client)
            ->data('MerchantID', '2222-2222-2222-2222')
            ->request();
        $this->assertDataInRequest('2222-2222-2222-2222', 'MerchantID');
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        Requester::make($this->validConfig(), $client)->amount(1000)->request();
        $this->assertDataInRequest(1000, 'Amount');

        Requester::make($this->validConfig(), $client)->data('Amount', 3500)->request();
        $this->assertDataInRequest(3500, 'Amount');
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        $this->app['router']->get('/verify-payment')->name('payment.callback');

        $client = $this->mockedPurchaseResponse(3);

        config(['toman.callback_route' => 'payment.callback']);
        Requester::make($this->validConfig(), $client)->request();
        $this->assertDataInRequest(URL::route('payment.callback'), 'CallbackURL');

        Requester::make($this->validConfig(), $client)->callback('https://example.com/callbackA')->request();
        $this->assertDataInRequest('https://example.com/callbackA', 'CallbackURL');

        Requester::make($this->validConfig(), $client)->data('CallbackURL', 'https://example.com/callbackB')->request();
        $this->assertDataInRequest('https://example.com/callbackB', 'CallbackURL');
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        $client = $this->mockedPurchaseResponse(4);

        config(['toman.description' => 'Paying :amount for invoice']);
        Requester::make($this->validConfig(), $client)->amount(5000)->request();
        $this->assertDataInRequest('Paying 5000 for invoice', 'Description');

        Requester::make($this->validConfig(), $client)->description('Some descriptions')->request();
        $this->assertDataInRequest('Some descriptions', 'Description');

        Requester::make($this->validConfig(), $client)->data('Description', 'Other text')->request();
        $this->assertDataInRequest('Other text', 'Description');
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        Requester::make($this->validConfig(), $client)->mobile('09350000000')->request();
        $this->assertDataInRequest('09350000000', 'Mobile');

        Requester::make($this->validConfig(), $client)->data('Mobile', '09351111111')->request();
        $this->assertDataInRequest('09351111111', 'Mobile');
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        $client = $this->mockedPurchaseResponse(2);

        Requester::make($this->validConfig(), $client)->email('amirreza@example.com')->request();
        $this->assertDataInRequest('amirreza@example.com', 'Email');

        Requester::make($this->validConfig(), $client)->data('Email', 'alireza@example.com')->request();
        $this->assertDataInRequest('alireza@example.com', 'Email');
    }

    /**
     * @test
     * @dataProvider sandboxProvider
     */
    public function validates_sandbox($passes, $sandbox)
    {
        $client = $this->mockedPurchaseResponse();

        $gateway = Requester::make($this->validConfig([
            'sandbox' => $sandbox
        ]), $client);

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
