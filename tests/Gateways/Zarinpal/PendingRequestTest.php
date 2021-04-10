<?php

namespace Evryn\LaravelToman\Tests\Gateways\Zarinpal;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway;
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
        // We need to ensure that developer can set merchant id elegantly, with their preferred methods

        $this->gateway->setConfig(['merchant_id' => 'AAA']);
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
            $this->factory->amount(5000)->description('Paying :amount for invoice 1')->description()
        );

        self::assertEquals(
            'Paying 5000 for invoice 2',
            $this->factory->description('Paying :amount for invoice 2')->amount(5000)->description()
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
}
