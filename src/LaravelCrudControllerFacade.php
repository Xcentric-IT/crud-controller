<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Support\Facades\Facade;

/**
 * @see \XcentricItFoundation\LaravelCrudController\Skeleton\SkeletonClass
 */
class LaravelCrudControllerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-crud-controller';
    }
}
