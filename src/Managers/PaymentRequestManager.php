<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Gateways\Zarinpal\Requester as ZarinpalPaymentRequest;
use Illuminate\Support\Manager;

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
        return new ZarinpalPaymentRequest(
            config('toman.gateways.zarinpal')
        );
    }
}
