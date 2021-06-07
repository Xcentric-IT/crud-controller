<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FilterIsNull implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (str_contains($property, ':')) {
            $property = explode(':', $property)[1];
        }
        $query->whereNull($property);
    }
}
