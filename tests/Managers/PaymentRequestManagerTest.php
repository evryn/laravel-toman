<?php

namespace AmirrezaNasiri\LaravelToman\Tests\Managers;

use AmirrezaNasiri\LaravelToman\Managers\PaymentRequestManager;
use AmirrezaNasiri\LaravelToman\Tests\TestCase;

final class PaymentRequestManagerTest extends TestCase
{
    /** @var PaymentRequestManager */
    public $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PaymentRequestManager($this->app);
    }

    /** @test */
    public function gets_default_driver_from_config()
    {
        config(['toman.default' => 'foo']);
        self::assertEquals('foo', $this->manager->getDefaultDriver());

        config(['toman.default' => 'bar']);
        self::assertEquals('bar', $this->manager->getDefaultDriver());
    }

    /** @test */
    public function creates_configured_zarinpal_drive()
    {
        $config = [
            'sandbox' => true,
            'merchant_id' => 'xxxxxxxx-yyyy-zzzz-wwww-xxxxxxxxxxxx',
        ];

        config(['toman.gateways.zarinpal' => $config]);

        $gateway = $this->manager->driver('zarinpal');

        self::assertEquals($config, $gateway->getConfig());
    }


}
