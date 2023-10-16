<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\CheckedPayment;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
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

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::endpointProvider()
     */
    public function can_verify_manually(bool $sandbox, string $baseUrl)
    {
        Http::fake([
            "$baseUrl/pg/rest/WebGate/PaymentVerification.json" => Http::response([
                'Status' => '100',
                'RefID' => '1000020000',
            ], 200),
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx',
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
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::endpointProvider()
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
            'Authority' => 'A0000012345',
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'merchant_id' => 'xxxx-xxxx-xxxx-xxxx',
        ]);

        $gateway = $this->factory->inspectCallbackRequest()->amount(1500);

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
     *
     * @dataProvider badTransactionId
     */
    public function validates_callback_transaction_id($value)
    {
        Http::fake();

        request()->merge([
            'Authority' => $value,
        ]);

        $this->expectException(ValidationException::class);

        $this->factory->inspectCallbackRequest();
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
    public function fails_with_server_error()
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
            } catch (GatewayServerException $exception) {
            }
        });
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::clientErrorProvider()
     */
    public function fails_with_client_error($httpStatus, $statusCode, $messageKey)
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
            'www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json' => Http::response([
                'Status' => '100',
                'RefID' => '1000020000',
            ], 200),
        ]);

        $this->factory->amount($actualAmount)->verify();

        Http::assertSent(function (Request $request) use ($expectedAmountValue) {
            return $request['Amount'] == $expectedAmountValue;
        });
    }
}
