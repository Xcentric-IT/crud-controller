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

$middleware = config('laravel-crud-controller.middlewares') ?? [];

Route::group(['middleware' => $middleware], function ($router) use ($url) {
    $router->get($url, function (string $model, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->readMore();
    });

    $router->get($url . '/{id}', function (string $model, $id, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->readOne($id);
    });

    $router->post($url, function (string $model, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->create();
    });

    $router->put($url . '/{id}', function (string $model, $id, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->update($id);
    });

    $router->delete($url . '/{id}', function (string $model, $id, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->delete($id);
    });

    $router->put($url . '/{id}/relation/{relationField}', function (string $model, $id, $relationField, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->addRemoveRelation($id, $relationField);
    });

    $router->delete($url . '/{id}/relation/{relationField}/{relationId}', function (string $model, $id, $relationField, $relationId, $namespace = 'app') {
        return resolveOrFail($namespace, $model)->addRemoveRelation($id, $relationField, $relationId, false);
    });
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
