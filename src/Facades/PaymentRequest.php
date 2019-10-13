<?php

namespace AmirrezaNasiri\LaravelToman\Facades;

use Illuminate\Support\Facades\Facade;
use AmirrezaNasiri\LaravelToman\Contracts\PaymentRequester;

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
