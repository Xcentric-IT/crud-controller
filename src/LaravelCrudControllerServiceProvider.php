<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Support\ServiceProvider;
use XcentricItFoundation\LaravelCrudController\Console\Commands\GenerateRoutes;
use XcentricItFoundation\LaravelCrudController\Providers\LaravelCrudRouteServiceProvider;

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

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-crud-controller.php'),
            ], 'config');

            $this->commands([
                GenerateRoutes::class
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-crud-controller');
        
        $this->app->register(LaravelCrudRouteServiceProvider::class);
    }
}
