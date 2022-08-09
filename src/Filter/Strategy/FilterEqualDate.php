<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FilterEqualDate extends BaseFilter
{
    protected function applyFilter(Builder $query, mixed $value, string $property): void
    {
        $startOfDay = Carbon::parse($value)->startOfDay();
        $endOfDay = Carbon::parse($value)->endOfDay();
        $query->whereBetween($property, [$startOfDay, $endOfDay]);
    }
}
