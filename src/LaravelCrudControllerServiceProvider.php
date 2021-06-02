<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Support\ServiceProvider;

class LaravelCrudControllerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
         $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-crud-controller.php'),
            ], 'config');

        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-crud-controller');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crud-controller', function () {
            return new LaravelCrudController;
        });
    }
}
