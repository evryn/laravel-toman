<?php

namespace Evryn\LaravelToman;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;

/**
 * Class CallbackRequest.
 *
 * @mixin PendingRequest
 */
class CallbackRequest implements ValidatesWhenResolved
{
    /** @var Factory|PendingRequest */
    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function validateResolved()
    {
        return $this->factory = $this->factory->inspectCallbackRequest();
    }

    public function __call($name, $arguments)
    {
        return $this->factory->{$name}(...$arguments);
    }
}
