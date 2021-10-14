<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Gateways\IDPay\Gateway as IDPayGateway;
use Evryn\LaravelToman\Gateways\Zarinpal\Gateway as ZarinpalGateway;
use Illuminate\Support\Manager;

class GatewayManager extends Manager
{
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
     *
     * @return ZarinpalGateway
     */
    public function createZarinpalDriver()
    {
        return new ZarinpalGateway(
            config('toman.gateways.zarinpal')
        );
    }

    /**
     * Create IDPay gateway driver.
     *
     * @return IDPayGateway
     */
    public function createIDPayDriver()
    {
        return new IDPayGateway(
            config('toman.gateways.idpay')
        );
    }
}
