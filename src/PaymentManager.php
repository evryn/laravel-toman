<?php

namespace AmirrezaNasiri\LaravelToman;

use Illuminate\Support\Manager;
use AmirrezaNasiri\LaravelToman\Gateways\ZarinpalGateway;

class PaymentManager extends Manager
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

    public function createZarinpalDriver()
    {
        return new ZarinpalGateway(config('toman.gateways.zarinpal'));
    }
}
