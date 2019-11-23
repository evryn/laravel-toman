<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Clients\GuzzleClient;
use Evryn\LaravelToman\Gateways\Zarinpal\Verifier as ZarinpalPaymentVerification;
use Illuminate\Support\Manager;

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
