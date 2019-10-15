<?php


namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;


use Evryn\LaravelToman\Clients\GuzzleClient;
use Evryn\LaravelToman\Gateways\Zarinpal\Requester;
use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Exceptions\InvalidConfigException;
use Evryn\LaravelToman\Gateways\Zarinpal\Verifier;
use Evryn\LaravelToman\Tests\Gateways\DriverTestCase;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;


final class VerifierTest extends DriverTestCase
{
    /** @test */
    public function verifies_incoming_production_callback_correctly()
    {
        $this->withoutExceptionHandling();

        $client = $this->mockedVerificationResponse(100, '77777777');

        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        $verifiedPayment = Verifier::make([
            'merchant_id' => '1111-1111-1111-1111'
        ], $client)
            ->amount(1500)
            ->verify($request);

        $this->assertLastRequestedUrlEquals('https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
        $this->assertLastRequestedDataEquals([
            'MerchantID' => '1111-1111-1111-1111',
            'Amount' => '1500',
            'Authority' => '000000000000000000000000000000001234',
        ]);
        self::assertEquals('77777777', $verifiedPayment->getReferenceId());
    }

    /** @test */
    public function verifies_incoming_sandbox_callback_correctly()
    {
        $this->withoutExceptionHandling();

        $client = $this->mockedVerificationResponse(100, '77777777');

        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        $verifiedPayment = Verifier::make([
            'sandbox' => true,
            'merchant_id' => '1111-1111-1111-1111'
        ], $client)
            ->amount(1500)
            ->verify($request);

        $this->assertLastRequestedUrlEquals('https://sandbox.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
        $this->assertLastRequestedDataEquals([
            'MerchantID' => '1111-1111-1111-1111',
            'Amount' => '1500',
            'Authority' => '000000000000000000000000000000001234',
        ]);
        self::assertEquals('77777777', $verifiedPayment->getReferenceId());
    }

    /** @test */
    public function fails_without_verifying_when_status_is_not_ok()
    {
        $this->withoutExceptionHandling();
        $client = $this->mockedGuzzleClient(null);

        $request = Request::create('/callback', 'GET', [
            'Status' => 'NOK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage(Status::toMessage(Status::NOT_PAID));
        $this->expectExceptionCode(Status::NOT_PAID);

        Verifier::make($this->validConfig(), $client)
            ->amount(5000)
            ->verify($request);
    }

    /** @test */
    public function converts_gateway_validation_error_to_exception()
    {
        $client = $this->mockedGuzzleClient(new Response(404, [], json_encode([
            'Status' => -11,
            'Authority' => '',
            'errors' => [
                'Authority' => 'The authority field is required.',
                'Amount' => 'The amount field is required.',
            ]
        ])));

        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('The authority field is required.');
        $this->expectExceptionCode(-11);

        Verifier::make($this->validConfig(), $client)
            ->amount(5000)
            ->verify($request);
    }

    /**
     * @test
     * @dataProvider errorProvider
     */
    public function converts_validation_error_to_exception($passes, $httpCode, $status)
    {
        $client = $this->mockedGuzzleClient(new Response($httpCode, [], json_encode([
            'Status' => $status,
            'RefID' => $status === 100 ? '1234' : 0
        ])));

        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        try {
            Verifier::make($this->validConfig(), $client)
                ->amount(5000)
                ->verify($request);
            self::assertTrue($passes);
        } catch (GatewayException $exception) {
            self::assertEquals($status, $exception->getCode());
            self::assertEquals(Status::toMessage($status), $exception->getMessage());
            return true;
        }

        if (!$passes)
            self::fail("Didn't thrown expected exception.");
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
    public function can_set_amount_elegantly()
    {
        $this->withoutExceptionHandling();
        $client = $this->mockedVerificationResponse(100, '123', 2);

        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        Verifier::make($this->validConfig(), $client)
            ->amount(1000)
            ->verify($request);
        $this->assertDataInRequest(1000, 'Amount');

        Verifier::make($this->validConfig(), $client)
            ->data('Amount', 2500)
            ->verify($request);
        $this->assertDataInRequest(2500, 'Amount');
    }

    /**
     * @test
     * @dataProvider sandboxProvider
     */
    public function validates_sandbox($passes, $sandbox)
    {
        $request = Request::create('/callback', 'GET', [
            'Status' => 'OK',
            'Authority' => '000000000000000000000000000000001234',
        ]);

        $client = $this->mockedVerificationResponse(100);

        $gateway = Verifier::make($this->validConfig([
            'sandbox' => $sandbox
        ]), $client);

        try {
            $gateway->verify($request);
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

    private function mockedVerificationResponse($status, $ref_id = 0, $times = 1)
    {
        $responses = [];
        foreach (range(1, $times) as $i) {
            $responses[] = new Response(200, [], json_encode([
                'Status' => $status,
                'RefID' => $ref_id,
            ]));
        }

        return $this->mockedGuzzleClient($responses);
    }
}
