<?php

namespace AmirrezaNasiri\LaravelToman\Facades;

use AmirrezaNasiri\LaravelToman\Contracts\PaymentRequester;
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
        return PaymentRequester::class;
    }
}