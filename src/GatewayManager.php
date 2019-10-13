<?php

namespace AmirrezaNasiri\LaravelToman;

use Illuminate\Support\Manager;
use AmirrezaNasiri\LaravelToman\Clients\GuzzleClient;
use AmirrezaNasiri\LaravelToman\Gateways\ZarinpalGateway;

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
     * @return ZarinpalGateway
     */
    public function createZarinpalDriver()
    {
        return ZarinpalGateway::make(
            config('toman.gateways.zarinpal'),
            app(GuzzleClient::class)
        );
    }
}
