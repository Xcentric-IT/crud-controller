<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\FiltersExact;

abstract class BaseFilter extends FiltersExact
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        if ($this->addRelationConstraint && $this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $value, $property);

            return;
        }

        $this->applyFilter($query, $value, $property);
    }

    abstract protected function applyFilter(Builder $query, mixed $value, string $property): void;
}
