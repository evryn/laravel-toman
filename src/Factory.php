<?php


namespace Evryn\LaravelToman;


use Evryn\LaravelToman\Interfaces\PendingRequestInterface;
use Evryn\LaravelToman\Managers\PendingRequestManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Macroable;

class Factory
{
    /**
     * @var Container
     */
    private $container;

    use Macroable {
        __call as macroCall;
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function gateway(string $name = null, array $config = [])
    {
        $gateway = (new PendingRequestManager($this->container, $this))->driver($name);

        if ($config) {
            $gateway->config($config);
        }

        return $gateway;
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap($this->gateway(), function ($request) {
            // $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }
}
