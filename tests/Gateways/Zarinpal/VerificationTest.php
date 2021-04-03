<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\CheckedPayment;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class VerificationTest extends TestCase
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
    public function can_verify_by_transaction_id(bool $sandbox, string $baseUrl)
    {
        Http::fake([
            "$baseUrl/pg/rest/WebGate/PaymentVerification.json" => Http::response([
                'Status' => '100',
                'RefID' => '1000020000',
            ], 200),
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ]);

        tap(
            $this->factory
                ->amount(1500)
                ->transactionId('A0000012345')
                ->verify(),
            function (CheckedPayment $request) use ($baseUrl) {
                Http::assertSent(function (Request $request) use ($baseUrl) {
                    return $request->method() === 'POST'
                        && $request->url() === "https://$baseUrl/pg/rest/WebGate/PaymentVerification.json"
                        && $request['MerchantID'] === 'xxxx-xxxx-xxxx-xxxx'
                        && $request['Amount'] == 1500
                        && $request['Authority'] === 'A0000012345';
                });

                // Since request is successful, we need to ensure that nothing can be
                // thrown and determiners are correct
                $request->throw();
                self::assertTrue($request->successful());
                self::assertFalse($request->alreadyVerified());
                self::assertFalse($request->failed());

                self::assertEquals('A0000012345', $request->transactionId());
                self::assertEquals('1000020000', $request->referenceId());
            }
        );
    }

    /**
     * @test
     * @dataProvider endpointProvider
     */
    public function can_verify_callback_request(bool $sandbox, string $baseUrl)
    {
        Http::fake([
            "$baseUrl/pg/rest/WebGate/PaymentVerification.json" => Http::response([
                'Status' => '100',
                'RefID' => '1000020000',
            ], 200),
        ]);

        request()->merge([
            'Authority' => 'A0000012345'
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx'
        ]);

        $gateway = $this->factory->amount(1500);

        tap($gateway->verify(), function (CheckedPayment $request) use ($baseUrl) {

            Http::assertSent(function (Request $request) use ($baseUrl) {
                return $request->method() === 'POST'
                    && $request->url() === "https://$baseUrl/pg/rest/WebGate/PaymentVerification.json"
                    && $request['MerchantID'] === 'xxxx-xxxx-xxxx-xxxx'
                    && $request['Amount'] == 1500
                    && $request['Authority'] === 'A0000012345';
            });

            $request->throw();
            self::assertTrue($request->successful());
            self::assertFalse($request->alreadyVerified());
            self::assertFalse($request->failed());

            self::assertEquals('A0000012345', $request->transactionId());
            self::assertEquals('1000020000', $request->referenceId());
        });
    }

    public static function badTransactionId()
    {
        return [
            'Empty' => [''],
            'Array' => [['A0000012345']],
        ];
    }

    /**
     * @test
     * @dataProvider badTransactionId
     */
    public function validates_callback_transaction_id($value)
    {
        Http::fake();

        request()->merge([
            'Authority' => $value
        ]);

        $this->expectException(ValidationException::class);

        $this->factory->verify();
    }

    /** @test */
    public function can_determine_if_transaction_has_already_been_verified()
    {
        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json' => Http::response([
                'Status' => '101',
                'RefID' => '1000020000',
            ], 200),
        ]);

        $gateway = $this->factory
            ->amount(1500)
            ->transactionId('A0000012345');

        tap($gateway->verify(), function (CheckedPayment $request) {
            $request->throw();
            self::assertFalse($request->successful());
            self::assertTrue($request->alreadyVerified());
            self::assertFalse($request->failed());

            self::assertEquals('A0000012345', $request->transactionId());
            self::assertEquals('1000020000', $request->referenceId());
        });
    }

    /** @test */
    public function fails_with_gateway_exception()
    {
        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json' => Http::response(null, 555),
        ]);

        $gateway = $this->factory->transactionId('A0000012345');

        tap($gateway->verify(), function (CheckedPayment $verification) {
            self::assertFalse($verification->successful());
            self::assertFalse($verification->alreadyVerified());
            self::assertTrue($verification->failed());

            try {
                $verification->throw();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayServerException $exception) {
                self::assertEquals(555, $exception->getCode());
                self::assertEquals($exception->getMessage(), $verification->message());
                self::assertEquals($exception->getCode(), $verification->status());
            }

            self::assertNotEmpty($verification->transactionId());

            try {
                $verification->referenceId();
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
            [404, Status::UNEXPECTED, 'unexpected'],
        ];
    }

    /**
     * @test
     * @dataProvider clientErrorProvider
     */
    public function fails_with_client_exception_without_message($httpStatus, $statusCode, $messageKey)
    {
        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json' => Http::response([
                'Status' => $statusCode,
            ], $httpStatus),
        ]);

        $gateway = $this->factory->transactionId('A0000012345');

        tap($gateway->verify(), function (CheckedPayment $verification) use ($statusCode, $messageKey) {
            self::assertFalse($verification->successful());
            self::assertFalse($verification->alreadyVerified());
            self::assertTrue($verification->failed());

            try {
                $verification->throw();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
                self::assertEquals($statusCode, $exception->getCode());
                self::assertEquals($exception->getCode(), $verification->status());

                self::assertEquals(__("toman::zarinpal.status.$messageKey"), $exception->getMessage());
                self::assertEquals(__("toman::zarinpal.status.$messageKey"), $verification->message());
                self::assertEquals([__("toman::zarinpal.status.$messageKey")], $verification->messages());
            }

            self::assertNotEmpty($verification->transactionId());

            try {
                $verification->referenceId();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayClientException $exception) {}
        });
    }

    /** @test */
    public function can_set_merchant_id_elegantly()
    {
        // We need to ensure that developer can set merchant id elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->gateway->setConfig(['merchant_id' => '11111']);
        $this->factory->verify();
        Http::assertNthRequestFieldEquals('11111', 'MerchantID', 1);

        $this->factory->data('MerchantID', '33333')->verify();
        Http::assertNthRequestFieldEquals('33333', 'MerchantID', 2);

        $this->factory->merchantId('44444')->verify();
        Http::assertNthRequestFieldEquals('44444', 'MerchantID', 3);
    }

    /** @test */
    public function can_set_amount_elegantly()
    {
        // We need to ensure that developer can set amount elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->factory->data('Amount', '10000')->verify();
        Http::assertNthRequestFieldEquals('10000', 'Amount', 1);

        $this->factory->amount(20000)->verify();
        Http::assertNthRequestFieldEquals(20000, 'Amount', 2);
    }

    /** @test */
    public function can_set_transaction_id_elegantly()
    {
        // We need to ensure that developer can set transaction id elegantly, with their preferred methods

        $this->fakeValidResponse();

        $this->factory->data('Authority', 'A0001234')->verify();
        Http::assertNthRequestFieldEquals('A0001234', 'Authority', 1);

        $this->factory->transactionId('A0001234')->verify();
        Http::assertNthRequestFieldEquals('A0001234', 'Authority', 2);
    }

    private function fakeValidResponse(): void
    {
        Http::fake([
            'www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json' => Http::response([
                'Status' => '100',
                'RefID' => '10001000',
            ], 200),
        ]);
    }
}
