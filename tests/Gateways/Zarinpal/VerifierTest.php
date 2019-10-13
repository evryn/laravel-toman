<?php


namespace AmirrezaNasiri\LaravelToman\Tests\Gateways\Zarinpal;


use AmirrezaNasiri\LaravelToman\Clients\GuzzleClient;
use AmirrezaNasiri\LaravelToman\Gateways\Zarinpal\Requester;
use AmirrezaNasiri\LaravelToman\Exceptions\GatewayException;
use AmirrezaNasiri\LaravelToman\Exceptions\InvalidConfigException;
use AmirrezaNasiri\LaravelToman\Gateways\Zarinpal\Verifier;
use AmirrezaNasiri\LaravelToman\Tests\Gateways\DriverTestCase;
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
        $this->expectExceptionMessage('تراکنش ناموفق بوده یا توسط کاربر لغو شده است.');
        $this->expectExceptionCode(-22);

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
    public function converts_validation_error_to_exception($httpCode, $status, $partialMessage)
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
        } catch (GatewayException $exception) {
            self::assertEquals($status, $exception->getCode());
            self::assertStringContainsString($partialMessage, $exception->getMessage());
            return true;
        }

        self::fail("Didn't thrown expected exception.");
    }

    public function errorProvider()
    {
        return [
            [404, '-1', 'ناقص'],
            [404, '-2', 'مرچنت'],
            [404, '-3', 'محدوديت هاي شاپرك'],
            [404, '-4', 'سطح تاييد'],
            [404, '-11', 'يافت نشد'],
            [404, '-12', 'ويرايش درخواست'],
            [404, '-21', 'عمليات مالي'],
            [404, '-22', 'ناموفق'],
            [404, '-33', 'رقم تراكنش'],
            [404, '-34', 'سقف تقسيم'],
            [404, '-40', 'دسترسي به متد'],
            [404, '-41', 'AdditionalData'],
            [404, '-42', 'عمر شناسه'],
            [404, '-54', 'آرشيو'],
            [404, '101', 'قبلا'],
            [404, '-99999', 'unknown'],
            [200, '-1', 'ناقص'], // In case of switching 404 on failure since it's not been documented
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
