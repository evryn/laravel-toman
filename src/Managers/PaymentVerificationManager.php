<?php

namespace Evryn\LaravelToman\Managers;

use Illuminate\Support\Manager;
use Evryn\LaravelToman\Clients\GuzzleClient;
use Evryn\LaravelToman\Gateways\Zarinpal\Verifier as ZarinpalPaymentVerification;

class PaymentVerificationManager extends Manager
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
     * @return ZarinpalPaymentVerification
     */
    public function createZarinpalDriver()
    {
        return ZarinpalPaymentVerification::make(
            config('toman.gateways.zarinpal'),
            app(GuzzleClient::class)
        );
    }
}
