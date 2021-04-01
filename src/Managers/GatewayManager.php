<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway as ZarinpalGateway;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class GatewayManager extends Manager
{
    /**
     * @var array
     */
    private $gatewayConfig;

    public function __construct(Container $container, $gatewayConfig = null)
    {
        parent::__construct($container);
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Get the default payment gateway name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('toman.default');
    }

    /**
     * Create Zarinpal gateway driver.
     * @return ZarinpalGateway
     */
    public function createZarinpalDriver()
    {
        return new ZarinpalGateway(
            !is_null($this->gatewayConfig) ? $this->gatewayConfig : config('toman.gateways.zarinpal')
        );
    }
}
