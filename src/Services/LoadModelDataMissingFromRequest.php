<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\LaravelCrudRequest;

class LoadModelDataMissingFromRequest
{
    public function load(Model $model, string $requestClassFqn): array
    {
        /** @var LaravelCrudRequest $requestClass */
        $requestClass = new $requestClassFqn;

        $relations = [];
        foreach (array_keys($requestClass->rules()) as $fieldName) {
            if ($model->isRelation($fieldName)) {
                $relations[] = $fieldName;
            }
        }

        if (count($relations) > 0) {
            $model->load($relations);
        }

        return $model->toArray();
    }
}
