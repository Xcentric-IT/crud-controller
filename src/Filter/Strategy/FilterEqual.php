<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;

class FilterEqual extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        $query->where($property, '=', $value);
    }
}
