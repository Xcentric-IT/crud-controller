<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Support\Str;

/**
 * Class ModelHelper
 * @package App\Helpers
 */
class ModelHelper
{
    public static function getControllerName(string $modelName): string
    {
        return "{$modelName}Controller";
    }

    public static function getControllerFqn(string $modelName, ?string $namespace): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace ?? 'app');
        return "{$namespaceName}\\Http\\Controllers\\{$modelName}Controller";
    }

    public static function getRequestValidatorFqn(string $modelName, ?string $namespace): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace ?? 'app');
        return "{$namespaceName}\\Http\\Requests\\{$modelName}Request";
    }

    public static function getModelFqn(string $modelName, ?string $namespace): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace ?? 'app');
        return "{$namespaceName}\\Models\\$modelName";
    }

    public static function buildModelName(string $name): string
    {
        return ucfirst(Str::camel($name));
    }
}
