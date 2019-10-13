<?php

namespace Evryn\LaravelToman\Managers;

use Illuminate\Support\Manager;
use Evryn\LaravelToman\Clients\GuzzleClient;
use Evryn\LaravelToman\Gateways\Zarinpal\Requester as ZarinpalPaymentRequest;

class PaymentRequestManager extends Manager
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
     * @return ZarinpalPaymentRequest
     */
    public function createZarinpalDriver()
    {
        return ZarinpalPaymentRequest::make(
            config('toman.gateways.zarinpal'),
            app(GuzzleClient::class)
        );
    }
}
