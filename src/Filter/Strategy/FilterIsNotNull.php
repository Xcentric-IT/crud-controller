<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;

class FilterIsNotNull extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        $query->whereNotNull($property);
    }
}
