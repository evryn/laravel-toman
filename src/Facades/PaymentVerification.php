<?php

namespace Evryn\LaravelToman\Facades;

use Illuminate\Support\Facades\Facade;
use Evryn\LaravelToman\Contracts\PaymentVerifier;

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
