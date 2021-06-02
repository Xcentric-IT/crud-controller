<?php

namespace XcentricItFoundation\LaravelCrudController\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FiltersIsNull implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereNull($property);
    }
}
