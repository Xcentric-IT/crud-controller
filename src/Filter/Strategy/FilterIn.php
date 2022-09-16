<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;

class FilterIn extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        $query->whereIn($property, $value);
    }
}
