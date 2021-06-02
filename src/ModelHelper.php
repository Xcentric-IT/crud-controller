<?php

namespace XcentricItFoundation\LaravelCrudController;

use Str;

/**
 * Class ModelHelper
 * @package App\Helpers
 */
class ModelHelper
{
    public static function getControllerNamespace(string $namespace, string $modelName): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace);
        return "{$namespaceName}\\Http\\Controllers\\{$modelName}Controller";
    }

    public static function getRequestValidatorNamespace(string $namespace, string $modelName): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace);
        return "{$namespaceName}\\Http\\Requests\\{$modelName}Request";
    }

    public static function getModelNamespace(string $namespace, string $modelName): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace);
        return "{$namespaceName}\\Models\\$modelName";
    }

    public static function buildModelName(string $name): string
    {
        return ucfirst(Str::camel($name));
    }
}
