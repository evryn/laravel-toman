<?php

namespace Evryn\LaravelToman\Facades;

use Evryn\LaravelToman\Interfaces\PaymentRequesterInterface;
use Illuminate\Support\Facades\Facade;

class PaymentRequest extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PaymentRequesterInterface::class;
    }
}
