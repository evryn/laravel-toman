<?php

namespace Evryn\LaravelToman\Managers;

use Evryn\LaravelToman\Factory;
use Evryn\LaravelToman\Gateways\Zarinpal\PendingRequest as ZarinpalPendingRequest;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class PendingRequestManager extends Manager
{
    /**
     * @var Factory
     */
    private $factory;

    public function __construct(Container $container, Factory $factory)
    {
        parent::__construct($container);
        $this->factory = $factory;
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
     * @return ZarinpalPendingRequest
     */
    public function createZarinpalDriver()
    {
        return new ZarinpalPendingRequest(
            $this->factory,
            config('toman.gateways.zarinpal')
        );
    }
}
