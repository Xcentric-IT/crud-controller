<?php

use XcentricItFoundation\LaravelCrudController\ModelHelper;
use Illuminate\Support\Facades\Route;
use \XcentricItFoundation\LaravelCrudController\LaravelCrudController;

Route::prefix('{namespace}')->group(function($namespace) {
    Route::get('{model}', function (string $model) use ($namespace) {
        return resolveOrFail($namespace, $model)->readMore();
    });

    Route::get('{model}/{id}', function (string $model, $id) use ($namespace) {
        return resolveOrFail($namespace, $model)->readOne($id);
    });

    Route::post('{model}', function (string $model) use ($namespace) {
        return resolveOrFail($namespace, $model)->create();
    });

    Route::put('{model}/{id}', function (string $model, $id) use ($namespace) {
        return resolveOrFail($namespace, $model)->update($id);
    });

    Route::delete('{model}/{id}', function (string $model, $id) use ($namespace) {
        return resolveOrFail($namespace, $model)->delete($id);
    });
});

if (!function_exists('resolveOrFail')) {
    function resolveOrFail(string $namespace, string $model): LaravelCrudController
    {
        $controllerClass = ModelHelper::getControllerNamespace($namespace, $model);

        if (class_exists($controllerClass)) {
            return resolve($controllerClass);
        }

        return resolve(LaravelCrudController::class);
    }
}
