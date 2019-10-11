<?php

namespace AmirrezaNasiri\LaravelToman\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Notification;

class Payment extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-toman.payment';
    }
}
