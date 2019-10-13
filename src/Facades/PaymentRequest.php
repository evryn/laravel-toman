<?php

namespace Evryn\LaravelToman\Facades;

use Illuminate\Support\Facades\Facade;
use Evryn\LaravelToman\Contracts\PaymentRequester;

class PaymentRequest extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PaymentRequester::class;
    }
}
