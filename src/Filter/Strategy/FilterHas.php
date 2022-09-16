<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;

class FilterHas extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        if (!$value) {
            $query->doesntHave($property);
            return;
        }

        $query->has($property);
    }
}
