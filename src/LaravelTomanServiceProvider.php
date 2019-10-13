<?php

namespace AmirrezaNasiri\LaravelToman;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use AmirrezaNasiri\LaravelToman\Clients\GuzzleClient;

class LaravelTomanServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/toman.php', 'toman');

        // Register the PaymentManager used to separate drivers
        $this->app->singleton('laravel-toman.payment', function ($app) {
            return new GatewayManager($app);
        });

        // Register the Guzzle HTTP client used by drivers to send requests
        $this->app->singleton(GuzzleClient::class, function () {
            return new Client;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function provides()
    {
        return [
            'laravel-toman.payment',
            GuzzleClient::class,
        ];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/toman.php' => config_path('toman.php'),
        ], 'laravel-toman.config');
    }
}
