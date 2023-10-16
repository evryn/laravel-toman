<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\CallbackRequest;
use Evryn\LaravelToman\Exceptions\GatewayClientException;
use Evryn\LaravelToman\Exceptions\GatewayServerException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\IDPay\CheckedPayment;
use Evryn\LaravelToman\Gateways\IDPay\Gateway;
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

    /** @var CallbackRequest */
    protected $callbackRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway();
        $this->factory = new Factory($this->gateway);
        $this->callbackRequest = new CallbackRequest($this->factory);
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::endpointProvider()
     */
    public function can_verify_manually(bool $sandbox)
    {
        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response([
                'status' => 100,
                'payment' => [
                    'track_id' => 'rid_1000',
                ],
            ], 200),
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'api_key' => 'xxxx-xxxx-xxxx-xxxx',
        ]);

        tap(
            $this->factory
                ->orderId('order_1')
                ->transactionId('tid_1')
                ->verify(),
            function (CheckedPayment $request) use ($sandbox) {
                Http::assertSent(function (Request $request) use ($sandbox) {
                    return ($sandbox ? $request->header('X-SANDBOX')[0] === '1' : ! $request->hasHeader('X-SANDBOX'))
                        && $request->header('X-API-KEY')[0] === 'xxxx-xxxx-xxxx-xxxx'
                        && $request->method() === 'POST'
                        && $request->isJson()
                        && $request->url() === 'https://api.idpay.ir/v1.1/payment/verify'
                        && $request['order_id'] === 'order_1'
                        && $request['id'] == 'tid_1';
                });

                // Since request is successful, we need to ensure that nothing can be
                // thrown and determiners are correct
                $request->throw();
                self::assertTrue($request->successful());
                self::assertFalse($request->alreadyVerified());
                self::assertFalse($request->failed());

                self::assertEquals('order_1', $request->orderId());
                self::assertEquals('tid_1', $request->transactionId());
                self::assertEquals('rid_1000', $request->referenceId());
            }
        );
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::endpointProvider()
     */
    public function can_verify_callback_request(bool $sandbox)
    {
        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response([
                'status' => 100,
                'payment' => [
                    'track_id' => 'rid_1000',
                ],
            ], 200),
        ]);

        request()->merge([
            'id' => 'tid_1',
            'order_id' => 'order_1',
        ]);

        $this->gateway->setConfig([
            'sandbox' => $sandbox,
            'api_key' => 'xxxx-xxxx-xxxx-xxxx',
        ]);

        $gateway = $this->callbackRequest->validateResolved();

        tap($gateway->verify(), function (CheckedPayment $request) use ($sandbox) {
            Http::assertSent(function (Request $request) use ($sandbox) {
                return ($sandbox ? $request->header('X-SANDBOX')[0] === '1' : ! $request->hasHeader('X-SANDBOX'))
                    && $request->header('X-API-KEY')[0] === 'xxxx-xxxx-xxxx-xxxx'
                    && $request->method() === 'POST'
                    && $request->isJson()
                    && $request->url() === 'https://api.idpay.ir/v1.1/payment/verify'
                    && $request['order_id'] === 'order_1'
                    && $request['id'] == 'tid_1';
            });

            $request->throw();
            self::assertTrue($request->successful());
            self::assertFalse($request->alreadyVerified());
            self::assertFalse($request->failed());

            self::assertEquals('order_1', $request->orderId());
            self::assertEquals('tid_1', $request->transactionId());
            self::assertEquals('rid_1000', $request->referenceId());
        });
    }

    public static function badId()
    {
        return [
            'Empty' => [''],
            'Array' => [['some-id']],
        ];
    }

    /**
     * @test
     *
     * @dataProvider badId
     */
    public function validates_callback_transaction_id($value)
    {
        Http::fake();

        request()->merge([
            'id' => $value,
            'order_id' => 'order_1',
        ]);

        $this->expectException(ValidationException::class);

        $this->callbackRequest->validateResolved();
    }

    /**
     * @test
     *
     * @dataProvider badId
     */
    public function validates_callback_order_id($value)
    {
        Http::fake();

        request()->merge([
            'id' => 'tid_1000',
            'order_id' => $value,
        ]);

        $this->expectException(ValidationException::class);

        $this->callbackRequest->validateResolved();
    }

    /** @test */
    public function can_determine_if_transaction_has_already_been_verified()
    {
        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response([
                'status' => 101,
                'payment' => [
                    'track_id' => 'rid_1000',
                ],
            ], 200),
        ]);

        $gateway = $this->factory
            ->orderId('order_1')
            ->transactionId('tid_1000');

        tap($gateway->verify(), function (CheckedPayment $request) {
            $request->throw();
            self::assertFalse($request->successful());
            self::assertTrue($request->alreadyVerified());
            self::assertFalse($request->failed());

            self::assertEquals('order_1', $request->orderId());
            self::assertEquals('tid_1000', $request->transactionId());
            self::assertEquals('rid_1000', $request->referenceId());
        });
    }

    /** @test */
    public function fails_with_server_error()
    {
        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response(null, 555),
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
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\IDPay\Provider::clientErrorProvider()
     */
    public function fails_with_client_error($httpStatus, $statusCode)
    {
        Http::fake([
            'https://api.idpay.ir/v1.1/payment/verify' => Http::response([
                'error_code' => $statusCode,
                'error_message' => 'Something failed ...',
            ], $httpStatus),
        ]);

        $gateway = $this->factory->transactionId('A0000012345');

        tap($gateway->verify(), function (CheckedPayment $verification) use ($statusCode) {
            self::assertFalse($verification->successful());
            self::assertFalse($verification->alreadyVerified());
            self::assertTrue($verification->failed());

            try {
                $verification->throw();
                $this->fail('GatewayClientException has no thrown.');
            } catch (GatewayClientException $exception) {
                self::assertEquals($statusCode, $exception->getCode());
                self::assertEquals($exception->getCode(), $verification->status());

                self::assertEquals('Something failed ...', $exception->getMessage());
                self::assertEquals('Something failed ...', $verification->message());
                self::assertEquals(['Something failed ...'], $verification->messages());
            }

            self::assertNotEmpty($verification->transactionId());

            try {
                $verification->referenceId();
                $this->fail('GatewayServerException has no thrown.');
            } catch (GatewayClientException $exception) {
            }
        });
    }
}
