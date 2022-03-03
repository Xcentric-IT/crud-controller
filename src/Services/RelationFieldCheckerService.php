<?php

namespace XcentricItFoundation\LaravelCrudController\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class RelationFieldCheckerService
{
    public function isRelationField(Model $model, string $field): bool
    {
        return $model->isRelation($this->getRelationNameByField($field));
    }

    public function getRelationByField(Model $model, string $field): Relation
    {
        return $model->{$this->getRelationNameByField($field)}();
    }

    public function getRelationClassByField(Model $model, string $field): string
    {
        return get_class($this->getRelationByField($model, $field));
    }

    public function getRelationNameByField(string $field): string
    {
        return Str::camel($field);
    }
}
