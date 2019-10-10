<?php

namespace AmirrezaNasiri\LaravelToman;

use Illuminate\Support\ServiceProvider;

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

        // Register the service the package provides.
        $this->app->singleton('laravel-toman', function ($app) {
            return new LaravelToman;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-toman'];
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

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/amirrezanasiri'),
        ], 'laraveltoman.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/amirrezanasiri'),
        ], 'laraveltoman.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/amirrezanasiri'),
        ], 'laraveltoman.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
