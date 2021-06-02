<?php

use XcentricItFoundation\LaravelCrudController\ModelHelper;
use Illuminate\Support\Facades\Route;
use XcentricItFoundation\LaravelCrudController\LaravelCrudController;

$url = '';

if (config('laravel-crud-controller.routes-prefix')) {
    $url .= config('laravel-crud-controller.routes-prefix') . '/';
}

if (config('laravel-crud-controller.has-multiple-namespaces')) {
    $url .= '{namespace}/';
}

$url .= '{model}';

Route::get($url, function (string $model, $namespace = 'app') {
    return resolveOrFail($namespace, $model)->readMore();
});

Route::get($url . '/{id}', function (string $model, $id, $namespace = 'app') {
    return resolveOrFail($namespace, $model)->readOne($id);
});

Route::post($url, function (string $model, $namespace = 'app') {
    return resolveOrFail($namespace, $model)->create();
});

Route::put($url . '/{id}', function (string $model, $id, $namespace = 'app') {
    return resolveOrFail($namespace, $model)->update($id);
});

Route::delete($url . '/{id}', function (string $model, $id, $namespace = 'app') {
    return resolveOrFail($namespace, $model)->delete($id);
});

if (!function_exists('resolveOrFail')) {
    function resolveOrFail(string $namespace, string $model): LaravelCrudController
    {
        $controllerClass = ModelHelper::getControllerNamespace($model, $namespace);

        if (class_exists($controllerClass)) {
            return resolve($controllerClass);
        }

        return resolve(LaravelCrudController::class);
    }
}
