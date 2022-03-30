<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class FilterEqualDate implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        $startOfDay = Carbon::parse($value)->startOfDay();
        $endOfDay = Carbon::parse($value)->endOfDay();
        $query->whereBetween($property, [$startOfDay, $endOfDay]);
    }
}
