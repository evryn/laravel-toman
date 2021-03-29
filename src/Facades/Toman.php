<?php

namespace Evryn\LaravelToman\Facades;

use Evryn\LaravelToman\Factory;
use Illuminate\Support\Facades\Facade;

class Toman extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
