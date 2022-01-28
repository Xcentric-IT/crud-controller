<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;

class DetachBelongsToMany
{
    public function __invoke(Model $model, string $relationName, array $data)
    {
        if (!isset($data['id'])) {
            return;
        }

        $model->$relationName()->detach($data['id']);
    }
}
