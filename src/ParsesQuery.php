<?php

namespace XcentricItFoundation\LaravelCrudController;

use XcentricItFoundation\LaravelCrudController\Filters\FiltersIsNull;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

trait ParsesQuery
{
    /**
     * @param Request $request
     * @param string $model
     * @return QueryBuilder
     */
    public function parseRequest(Request $request, string $model): QueryBuilder
    {
        $request = $this->prepareRequest($request);
        $query = QueryBuilder::for($model, $request);
        $this->parseFilters($request, $query)
            ->parseSorts($request, $query)
            ->parseRelationships($request, $query);

        return $query;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function parseFilters(Request $request, QueryBuilder $queryBuilder): self
    {
        $filters = $request->input('filter', []);
        $allowedFilters = [];

        foreach ($filters as $filterName => $filterValue) {
            $allowedFilters[] = $this->getFilterMapping($filterName, $filterValue);
        }

        $queryBuilder->allowedFilters($allowedFilters);

        return $this;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function parseRelationships(Request $request, QueryBuilder $queryBuilder): self
    {
        $includes = explode(',', $request->input('include', ''));

        $queryBuilder->allowedIncludes($includes);

        return $this;
    }

    public function parseSorts(Request $request, QueryBuilder $queryBuilder): self
    {
        $sorts = explode(',', $request->input('sort', ''));

        $queryBuilder->allowedSorts(
            collect($sorts)->filter(function ($sort) {
                return !empty($sort);
            })->map(function ($sort) {
                return $this->getSortMapping(trim($sort));
            })->toArray()
        );

        return $this;
    }

    protected function getSortMapping(string $sort): string
    {
        return $sort;
    }

    /**
     * @param string $filter
     * @param string|null $value
     * @return AllowedFilter
     */
    protected function getFilterMapping(string $filter, ?string $value): AllowedFilter
    {
        if ($value === 'null') {
            return AllowedFilter::custom($filter, new FiltersIsNull);
        }

        if (Str::endsWith($filter, ['_id', '.id'])) {
            return AllowedFilter::exact($filter);
        }

        return AllowedFilter::partial($filter);
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function prepareRequest(Request $request): Request
    {
        $filters = $request->input('filter', []);

        $request->query->set('filter', $filters);
        return $request;
    }
}
