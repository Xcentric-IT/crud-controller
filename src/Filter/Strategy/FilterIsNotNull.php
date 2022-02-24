<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Str;

class FilterIsNotNull implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }
        $query->whereNotNull($property);
    }
}
