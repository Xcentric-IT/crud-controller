<?php

namespace XcentricItFoundation\LaravelCrudController;

use Str;

/**
 * Class ModelHelper
 * @package App\Helpers
 */
class ModelHelper
{
    public static function getControllerNamespace(string $modelName, ?string $namespace): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace ?? 'app');
        return "{$namespaceName}\\Http\\Controllers\\{$modelName}Controller";
    }

    public static function getRequestValidatorNamespace(string $modelName, ?string $namespace): string
    {
        $modelName = self::buildModelName($modelName);
        $namespaceName = self::buildModelName($namespace ?? 'app');
        return "{$namespaceName}\\Http\\Requests\\{$modelName}Request";
    }

    public static function getModelNamespace(string $modelName, ?string $namespace): string
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
