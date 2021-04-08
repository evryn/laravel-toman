<?php

namespace Evryn\LaravelToman\Tests\Gateways\IDPay;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\IDPay\Gateway;
use Evryn\LaravelToman\Money;
use Evryn\LaravelToman\Tests\TestCase;

final class PendingRequestTest extends TestCase
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
    public function can_set_merchant_id_elegantly()
    {
        $this->gateway->setConfig(['api_key' => 'AAA']);
        self::assertEquals('AAA', $this->factory->merchantId());

        self::assertEquals(
            'BBB',
            $this->factory->merchantId('BBB')->merchantId()
        );
    }

    /** @test */
    public function can_set_callback_url_elegantly()
    {
        $this->app['router']->get('/callback1')->name('payment.callback');

        config(['toman.callback_route' => 'payment.callback']);
        self::assertEquals(route('payment.callback'), $this->factory->callback());

        self::assertEquals(
            'https://example.com',
            $this->factory->callback('https://example.com')->callback()
        );
    }

    /** @test */
    public function can_set_description_elegantly()
    {
        config(['toman.description' => 'Paying :amount for invoice 1']);
        self::assertEquals(
            'Paying 5000 for invoice 1',
            $this->factory->amount(Money::Rial(5000))->description('Paying :amount for invoice 1')->description()
        );

        self::assertEquals(
            'Paying 5000 for invoice 2',
            $this->factory->description('Paying :amount for invoice 2')->amount(Money::Rial(5000))->description()
        );
    }

    /** @test */
    public function can_set_mobile_elegantly()
    {
        self::assertEquals(
            '09350000000',
            $this->factory->mobile('09350000000')->mobile()
        );
    }

    /** @test */
    public function can_set_email_elegantly()
    {
        self::assertEquals(
            'amirreza@exmaple.com',
            $this->factory->email('amirreza@exmaple.com')->email()
        );
    }

    /** @test */
    public function can_set_name_elegantly()
    {
        self::assertEquals(
            'amirreza',
            $this->factory->name('amirreza')->name()
        );
    }

    /** @test */
    public function can_set_order_id_elegantly()
    {
        self::assertEquals(
            'order10000',
            $this->factory->orderId('order10000')->orderId()
        );
    }
}
