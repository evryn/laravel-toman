<?php

namespace AmirrezaNasiri\LaravelToman;

use AmirrezaNasiri\LaravelToman\Gateways\ZarinpalGateway;
use Illuminate\Support\Manager;

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
