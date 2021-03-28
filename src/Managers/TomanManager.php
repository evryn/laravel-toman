<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest as ZarinpalPendingRequest;
use Illuminate\Support\Manager;

class TomanManager extends Manager
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
     * @return ZarinpalPendingRequest
     */
    public function createZarinpalDriver()
    {
        return new ZarinpalPendingRequest(
            config('toman.gateways.zarinpal')
        );
    }
}
