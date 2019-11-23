<?php

namespace Evryn\LaravelToman\Facades;

use Evryn\LaravelToman\Contracts\PaymentVerifier;
use Illuminate\Support\Facades\Facade;

class PaymentVerification extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PaymentVerifier::class;
    }
}
