<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;

class FilterStartsWith extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        $query->where($property, 'like', $value . '%');
    }
}
