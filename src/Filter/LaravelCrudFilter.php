<?php

namespace XcentricItFoundation\LaravelCrudController\Filter;


use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterDateSearch;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterAfterDate;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterBeforeDate;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterExact;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterIsNull;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterMultiFieldSearch;

/**
 * Class LaravelCrudFilter
 * @package XcentricItFoundation\LaravelCrudController\Filter
 */
class LaravelCrudFilter
{
    private $availableFilters = [
        'isNull' => FilterIsNull::class,
        'multiFieldSearch' => FilterMultiFieldSearch::class,
        'eq' => FilterExact::class,
        'beforeDate' => FilterBeforeDate::class,
        'afterDate' => FilterAfterDate::class,
        'dateSearch' => FilterDateSearch::class
    ];

    public function parseFilters(Request $request, QueryBuilder $queryBuilder): void
    {
        $filters = $request->input('filter', []);
        $allowedFilters = [];

        foreach ($filters as $filterName => $filterValue) {
            $allowedFilters[] = $this->getFilterMapping($filterName, $filterValue);
        }

        $queryBuilder->allowedFilters($allowedFilters);
    }

    /**
     * @param string $filter
     * @param string|null $value
     * @return AllowedFilter
     */
    protected function getFilterMapping(string $property, ?string $value): AllowedFilter
    {
        $filter = $property;
        if (str_contains($property, ':')) {
            $filter = explode(':', $property)[0];

            if ($this->availableFilters[$filter]) {
                return AllowedFilter::custom($property, new $this->availableFilters[$filter]);
            }
        }

        return AllowedFilter::partial($filter);
    }
}