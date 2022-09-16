<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Filter;

use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Str;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterContains;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterEndsWith;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterEqualDate;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterEqual;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterGreaterThan;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterGreaterThanOrEqual;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterHas;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterIn;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterIsNotNull;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterIsNull;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterLowerThan;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterLowerThanOrEqual;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterMultiFieldSearch;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterNotEqual;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterNotIn;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterStartsWith;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterTreeView;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterTreeViewReverse;

class LaravelCrudFilter
{
    private array $availableFilters = [
        'isNull' => FilterIsNull::class,
        'isNotNull' => FilterIsNotNull::class,
        'multiFieldSearch' => FilterMultiFieldSearch::class,
        'eq' => FilterEqual::class,
        'notEq' => FilterNotEqual::class,
        'eqDate' => FilterEqualDate::class,
        'lt' => FilterLowerThan::class,
        'lte' => FilterLowerThanOrEqual::class,
        'gt' => FilterGreaterThan::class,
        'gte' => FilterGreaterThanOrEqual::class,
        'contains' => FilterContains::class,
        'startsWith' => FilterStartsWith::class,
        'endsWith' => FilterEndsWith::class,
        'in' => FilterIn::class,
        'notIn' => FilterNotIn::class,
        'has' => FilterHas::class,
        'treeView' => FilterTreeView::class,
        'treeViewReverse' => FilterTreeViewReverse::class,
    ];

    public function parseFilters(array $filters, array $additionalFilters, bool $stripRelationName = false): array
    {
        $allowedFilters = [];

        foreach ($filters as $filterName => $filterValue) {
            $allowedFilters[] = $this->getFilterMapping($filterName, $additionalFilters, $stripRelationName);
        }

        return $allowedFilters;
    }

    protected function getFilterMapping(string $property, array $additionalFilters, bool $stripRelationName = false): AllowedFilter
    {
        $filter = $property;

        $internalName = $this->getInternalName($property, $stripRelationName);

        if (Str::contains($property, ':')) {
            $filter = explode(':', $property)[0];

            if (array_key_exists($filter, $this->availableFilters)) {
                return AllowedFilter::custom($property, new $this->availableFilters[$filter], $internalName);
            }

            if (array_key_exists($filter, $additionalFilters)) {
                return AllowedFilter::custom($property, new $additionalFilters[$filter], $internalName);
            }
        }

        return AllowedFilter::exact($filter, $internalName);
    }

    private function getInternalName(string $property, bool $stripRelationName = false): string
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        return ($stripRelationName === true)
            ? substr($property, strrpos($property, '.') + 1)
            : $property;
    }
}
