<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Exceptions\GatewayException;
use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Status;
use Evryn\LaravelToman\Money;
use Evryn\LaravelToman\PendingRequest;
use Evryn\LaravelToman\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use PHPUnit\Framework\AssertionFailedError;

final class FakeRequestTest extends TestCase
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

    /** @test */
    public function can_fake_successful_request()
    {
        config([
            'toman.description' => 'Pay :amount',
            'toman.currency' => 'toman',
        ]);

        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->gateway->setConfig([
            'sandbox' => false,
            'merchant_id' => 'xxxx-yyyyy',
        ]);

        $request = $this->factory
            ->callback('http://example.com/callback')
            ->mobile('09350000000')
            ->amount(5000)
            ->data('CustomData', 'Example')
            ->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxx-yyyyy', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('09350000000', $request->mobile());
            self::assertTrue($request->amount()->is(Money::Toman(5000)));
            self::assertEquals('Pay 5000', $request->description());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        $request->throw();
        self::assertTrue($request->successful());
        self::assertFalse($request->failed());
        self::assertEquals('tid_100', $request->transactionId());
        self::assertNotEmpty($request->paymentUrl());
        self::assertInstanceOf(RedirectResponse::class, $request->pay());
    }

    /** @test */
    public function can_fake_failed_request()
    {
        config([
            'toman.description' => 'Pay :amount',
            'toman.currency' => 'toman',
        ]);

        $this->factory->fakeRequest()->failed('Your request has failed.', Status::WRONG_IP_OR_MERCHANT_ID);

        $this->gateway->setConfig([
            'sandbox' => false,
            'merchant_id' => 'xxxx-yyyyy',
        ]);

        $request = $this->factory
            ->callback('http://example.com/callback')
            ->mobile('09350000000')
            ->amount(5000)
            ->data('CustomData', 'Example')
            ->request();

        $this->factory->assertRequested(function (PendingRequest $request) {
            self::assertEquals('xxxx-yyyyy', $request->merchantId());
            self::assertEquals('http://example.com/callback', $request->callback());
            self::assertEquals('09350000000', $request->mobile());
            self::assertTrue($request->amount()->is(Money::Toman(5000)));
            self::assertEquals('Pay 5000', $request->description());
            self::assertEquals('Example', $request->data('CustomData'));

            return true;
        });

        self::assertFalse($request->successful());
        self::assertTrue($request->failed());

        try {
            $request->throw();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
            self::assertEquals(Status::WRONG_IP_OR_MERCHANT_ID, $e->getCode());
            self::assertEquals('Your request has failed.', $e->getMessage());
            self::assertEquals('Your request has failed.', $request->message());
            self::assertEquals(['Your request has failed.'], $request->messages());
        }

        try {
            $request->transactionId();
            self::fail();
        } catch (GatewayException $e) {
        }

        try {
            $request->pay();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
        }

        try {
            $request->paymentUrl();
            self::fail('Nothing is thrown.');
        } catch (GatewayException $e) {
        }
    }

    /** @test */
    public function assertion_fails_when_truth_check_is_false()
    {
        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->request();

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertRequested(function (PendingRequest $request) {
            return false;
        });
    }

    /**
     * @test
     *
     * @dataProvider \Evryn\LaravelToman\Tests\Gateways\Zarinpal\Provider::fakeTomanBasedAmountProvider()
     */
    public function can_assert_correct_fake_amount_in_currencies($configCurrency, $actualAmount, Money $expectedAmount)
    {
        config([
            'toman.currency' => $configCurrency,
        ]);

        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->amount($actualAmount)->request();

        $this->factory->assertRequested(function (PendingRequest $request) use ($expectedAmount) {
            return $request->amount()->is($expectedAmount);
        });
    }

    /** @test */
    public function can_not_assert_incorrect_fake_amount_in_currencies()
    {
        config([
            'toman.currency' => 'toman',
        ]);

        $this->factory->fakeRequest()
            ->withTransactionId('tid_100')
            ->successful();

        $this->factory->amount(10)->request();

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertRequested(function (PendingRequest $request) {
            return $request->amount()->is(Money::Rial(10));
        });
    }
}
