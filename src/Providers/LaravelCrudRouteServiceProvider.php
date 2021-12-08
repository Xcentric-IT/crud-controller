<?php

namespace XcentricItFoundation\LaravelCrudController\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class LaravelCrudRouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->routes(function () {
            $this->registerAppRoutes();
            $this->registerModulesRoutes();
        });
    }
    /**
     * Register all app routes
     */
    protected function registerAppRoutes(): void
    {
        $appRoutePath = app()->basePath('app') . DIRECTORY_SEPARATOR . 'Routes';
        // first register custom routes
        $customRoutesPath = $appRoutePath . DIRECTORY_SEPARATOR . 'custom';
        if (File::exists($customRoutesPath)) {
            foreach (File::files($customRoutesPath) as $routesFile) {
                $this->registerRoutes($routesFile);
            }
        }
        // register auto generated CRUD routes
        if (File::exists($appRoutePath . DIRECTORY_SEPARATOR . 'api.php')) {
            $this->registerRoutes($appRoutePath . DIRECTORY_SEPARATOR . 'api.php');
        }
    }

    /**
     * Register routes for all modules
     */
    protected function registerModulesRoutes(): void
    {
        $modules = File::directories(app()->basePath('modules'));
        foreach ($modules as $module) {
            $this->registerModuleRoutes($module);
        }
    }

    /**
     * Register routes for specific module
     *
     * @param string $module
     */
    protected function registerModuleRoutes(string $module): void
    {
        $routesDir = $module . DIRECTORY_SEPARATOR . 'Routes';
        $crudModuleRoutes = $routesDir . DIRECTORY_SEPARATOR . 'api.php';
        $customModuleRoutes = $routesDir . DIRECTORY_SEPARATOR . 'custom';
        if (File::exists($customModuleRoutes)) {
            foreach (File::files($customModuleRoutes) as $routesFile) {
                $this->registerRoutes($routesFile);
            }
        }
        if (File::exists($crudModuleRoutes)) {
            $this->registerRoutes($crudModuleRoutes);
        }
    }

    /**
     * Register routes from given file
     *
     * @param $filePath
     */
    protected function registerRoutes($filePath): void
    {
        if (File::exists($filePath) === false) {
            return;
        }
        Route::prefix(config('laravel-crud-controller.routes-prefix'))
            ->middleware(config('laravel-crud-controller.middlewares'))
            ->group($filePath);
    }
}
